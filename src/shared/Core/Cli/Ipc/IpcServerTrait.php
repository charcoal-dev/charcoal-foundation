<?php
declare(strict_types=1);

namespace App\Shared\Core\Cli\Ipc;

use App\Shared\Core\Cli\AppAwareCliProcess;
use App\Shared\Core\Cli\CliScriptState;
use App\Shared\Core\Ipc\Internal\InternalIpcFrame;
use App\Shared\Core\Ipc\Internal\IpcFrameCode;
use App\Shared\Core\Ipc\IpcService;
use App\Shared\Core\Ipc\IpcSocket;
use App\Shared\Exception\IpcSocketReadException;

/**
 * Trait IpcServerTrait
 * @package App\Shared\Core\Cli\Ipc
 * @mixin AppAwareCliProcess
 */
trait IpcServerTrait
{
    protected readonly IpcSocket $ipcServer;
    protected readonly IpcService $ipcServiceEnum;

    abstract protected function declareIpcSocketBinding(): IpcService;

    /**
     * @param InternalIpcFrame $frame
     * @return void
     */
    protected function handleIpcFrame(InternalIpcFrame $frame): void
    {
        if ($frame->frameCode === IpcFrameCode::CLI_EXEC_STATE) {
            $siblingState = CliScriptState::tryFrom($frame->data->raw());
            $this->inline(sprintf("{cyan}%s{/} reported state: {yellow}%s{/} from PID {blue}%d{/}",
                $frame->message,
                $siblingState ? $siblingState->name : "{red}Invalid{/}",
                $frame->pid
            ));

            return;
        }

        $this->inline("{gray}Message ignored!");
    }

    /**
     * @return IpcSocket
     */
    public function ipcSocket(): IpcSocket
    {
        return $this->ipcServer;
    }

    /**
     * @return IpcService
     */
    public function ipcServiceEnum(): IpcService
    {
        return $this->ipcServiceEnum;
    }

    /**
     * @return void
     */
    public function ipcServerOnConstructHook(): void
    {
        $this->inline("{cyan}Starting IPC server{/} {grey}...");
        $this->ipcServiceEnum = $this->declareIpcSocketBinding();
        $this->ipcServer = new IpcSocket($this->ipcServiceEnum->getBinding($this->getAppBuild()->directories));
        $this->print(" {magenta}" . basename($this->ipcServer->binding->socketFile));
    }

    /**
     * @return void
     */
    protected function ipcHandleMessages(): void
    {
        $this->inline("Reading {yellow}{invert} IPC {/} message(s) {grey}... ");

        try {
            $msgQueue = $this->ipcServer->receive();
        } catch (IpcSocketReadException $e) {
            $this->print("");
            $this->print("{red}Failed to read IPC socket");
            $this->print("\t{red}" . $e->getMessage());
            return;
        }

        $this->print("{invert}{yellow} " . count($msgQueue) . " {/}");

        if ($msgQueue) {
            foreach ($msgQueue as $msg) {
                $ipcFrame = null;

                try {
                    $ipcFrame = InternalIpcFrame::decode($msg->message);
                } catch (\Throwable $t) {
                    $this->print("{red}* Invalid message received");
                    $this->print("\t{grey}[" . get_class($t) . "]: " . $t->getMessage());
                }

                if ($ipcFrame) {
                    $this->inline(sprintf("\t[{magenta}%s{/}]: ", $ipcFrame->frameCode->name));
                    $this->handleIpcFrame($ipcFrame);
                    $this->print("");
                }
            }
        }
    }
}