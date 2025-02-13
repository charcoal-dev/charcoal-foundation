<?php
declare(strict_types=1);

namespace App\Shared\Core\Ipc;

use App\Shared\Exception\IpcSocketReadException;
use App\Shared\Exception\IpcSocketWriteException;
use Charcoal\OOP\Traits\NoDumpTrait;
use Charcoal\OOP\Traits\NotCloneableTrait;
use Charcoal\OOP\Traits\NotSerializableTrait;

/**
 * Class IpcSocket
 * @package App\Shared\Core\Ipc
 */
class IpcSocket
{
    private ?\Socket $socket = null;

    use NotCloneableTrait;
    use NotSerializableTrait;
    use NoDumpTrait;

    /**
     * @param IpcSocketBinding|null $binding
     */
    public function __construct(
        public readonly ?IpcSocketBinding $binding
    )
    {
        if ($this->binding) {
            @unlink($this->binding->socketFile);
            $this->socket = $this->createClientSocket();
            if (!socket_bind($this->socket, $this->binding->socketFile)) {
                throw new \RuntimeException("Failed to bind IPC socket: " .
                    socket_strerror(socket_last_error($this->socket)));
            }
        }
    }

    /**
     * @param IpcSocketBinding $recipient
     * @param string $message
     * @return void
     * @throws IpcSocketWriteException
     */
    public function send(IpcSocketBinding $recipient, string $message): void
    {
        $messageLen = strlen($message);
        if ($messageLen > $recipient->dataGramSize) {
            throw new \OverflowException(
                "Message exceeds recipient datagram size of " . $recipient->dataGramSize . " bytes"
            );
        }

        $sender = $this->socket ?? $this->createClientSocket();
        if (!@socket_sendto($sender, $message, $messageLen, 0, $recipient->socketFile)) {
            $error = socket_last_error($sender);
            throw new IpcSocketWriteException(socket_strerror($error), $error);
        }

        if (!$this->socket) {
            $this->closeClientSocket($sender);
        }
    }

    /**
     * @return IpcMessage[]
     * @throws IpcSocketReadException
     */
    public function receive(): array
    {
        if (!$this->socket || !$this->binding) {
            throw new \LogicException("Cannot receive messages on IpcSocket without IpcSocketBinding");
        }

        $queue = [];
        while (true) {
            $msgSender = "";
            $msgBuffer = "";
            $read = socket_recvfrom($this->socket, $msgBuffer, $this->binding->dataGramSize, 0, $msgSender);
            if ($read === false) {
                $ipcSocketError = socket_last_error($this->socket);
                if ($ipcSocketError === SOCKET_EAGAIN || $ipcSocketError === SOCKET_EWOULDBLOCK) {
                    break;
                }

                throw new IpcSocketReadException(socket_strerror($ipcSocketError), $ipcSocketError);
            }

            $queue[] = new IpcMessage($msgBuffer, $msgSender);
        }

        return $queue;
    }

    /**
     * @return \Socket
     */
    public function createClientSocket(): \Socket
    {
        $socket = socket_create(AF_UNIX, SOCK_DGRAM, 0);
        if ($socket === false) {
            throw new \RuntimeException("Failed to create socket: " .
                socket_strerror(socket_last_error()));
        }

        if (!socket_set_nonblock($socket)) {
            throw new \RuntimeException("Failed to set IPC client socket in NON-BLOCK mode. " .
                socket_strerror(socket_last_error($socket)));
        }

        return $socket;
    }

    /**
     * @param \Socket $socket
     * @return void
     */
    public function closeClientSocket(\Socket $socket): void
    {
        socket_close($socket);
    }

    /**
     * Closes socket connection on destruct
     */
    public function __destruct()
    {
        if ($this->socket) {
            $this->closeClientSocket($this->socket);
            $this->socket = null;
        }
    }
}