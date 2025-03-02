<?php
declare(strict_types=1);

namespace App\Shared\Core\Cli\Ipc;

use App\Shared\Context\IpcService;
use App\Shared\Core\Cli\AppAwareCliScript;
use App\Shared\Core\Ipc\Internal\InternalIpcFrame;
use App\Shared\Core\Ipc\Internal\IpcFrameCode;
use App\Shared\Core\Ipc\IpcSocket;
use App\Shared\Core\Ipc\IpcSocketBinding;
use Charcoal\Buffers\Buffer;

/**
 * Class IpcClientTrait
 * @package App\Shared\Core\Cli\Ipc
 * @mixin AppAwareCliScript
 */
trait IpcClientTrait
{
    /**
     * @param IpcService|IpcSocketBinding $recipient
     * @param InternalIpcFrame|string $message
     * @return void
     * @throws \App\Shared\Exception\IpcSocketWriteException
     */
    protected function ipcSendMessage(IpcService|IpcSocketBinding $recipient, InternalIpcFrame|string $message): void
    {
        $recipient = $recipient instanceof IpcService ?
            $recipient->getBinding($this->getAppBuild()->directories) : $recipient;

        $message = $message instanceof InternalIpcFrame ?
            $message->encode()->raw() : $message;

        $socket = $this instanceof IpcServerInterface ? $this->ipcSocket() : new IpcSocket(null);
        $socket->send($recipient, $message);
    }

    /**
     * @param string $message
     * @param IpcFrameCode $frameCode
     * @param Buffer|null $data
     * @return InternalIpcFrame
     */
    protected function ipcPrepareFrame(string $message, IpcFrameCode $frameCode, ?Buffer $data): InternalIpcFrame
    {
        return new InternalIpcFrame(
            $message,
            getmypid(),
            $this instanceof IpcServerInterface ? $this->ipcServiceEnum() : null,
            $frameCode,
            $data
        );
    }

    /**
     * @param IpcService|IpcSocketBinding $recipient
     * @param string $whoAmI
     * @return void
     * @throws \App\Shared\Exception\IpcSocketWriteException
     */
    protected function ipcSendCurrentState(IpcService|IpcSocketBinding $recipient, string $whoAmI): void
    {
        $this->ipcSendMessage($recipient, $this->ipcPrepareFrame(
            $whoAmI,
            IpcFrameCode::CLI_EXEC_STATE,
            new Buffer($this->getState()->value))
        );
    }
}