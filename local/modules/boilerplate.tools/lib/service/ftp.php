<?php

namespace Boilerplate\Tools\Service;

use Boilerplate\Tools\Exception\FtpException;

class Ftp
{
    private \FTP\Connection|bool|null $connection = null;

    /**
     * @throws FtpException
     */
    public function __construct(
        private readonly string $host,
        private readonly string $user,
        private readonly string $password,
        private readonly int $port = 21,
        private readonly int $timeout = 15,
        bool $ssl = true
    ) {
        // установка соединения
        if ($ssl) {
            $this->connection = \ftp_ssl_connect($this->host, $this->port, $this->timeout);
        } else {
            $this->connection = \ftp_connect($this->host, $this->port, $this->timeout);
        }

        // авторизация
        $login_result = \ftp_login($this->connection, $this->user, $this->password);

        if (!$login_result) {
            throw new FtpException("FTP: Connection error: $this->user@$this->host:$this->port");
        }

        // включение пассивного режима
        \ftp_pasv($this->connection, true);
    }

    public function __destruct()
    {
        \ftp_close($this->connection);
    }

    /**
     * @throws FtpException
     */
    public function downloadFile(string $remote_file, string $local_file): bool
    {
        if (!\ftp_get($this->connection, $local_file, $remote_file, FTP_BINARY)) {
            throw new FtpException("FTP: File download error: $remote_file");
        }

        return true;
    }

    public function getConnection()
    {
        return $this->connection;
    }
}
