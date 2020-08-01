<?php

declare(strict_types=1);

namespace xisrapilx\amysqlo\thread;

use pocketmine\snooze\SleeperNotifier;
use xisrapilx\amysqlo\thread\queue\QueryReceiveQueue;
use xisrapilx\amysqlo\thread\queue\QuerySendQueue;
use xisrapilx\mysqlo\credentials\Credentials;
use xisrapilx\mysqlo\exception\ConnectionException;

interface MySQLOThread{

    /**
     * MySQLOThread constructor.
     *
     * @param QuerySendQueue $querySendQueue
     * @param QueryReceiveQueue $receiveQueue
     * @param SleeperNotifier $queryNotifier
     * @param SleeperNotifier $connectionNotifier
     * @param Credentials $credentials
     * @param mixed ...$additional
     */
    public function __construct(QuerySendQueue $querySendQueue, QueryReceiveQueue $receiveQueue, SleeperNotifier $queryNotifier, SleeperNotifier $connectionNotifier, Credentials $credentials, ...$additional);

    public function getConnectionException() : ?ConnectionException;
}