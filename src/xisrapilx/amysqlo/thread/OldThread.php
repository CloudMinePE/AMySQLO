<?php

declare(strict_types=1);

namespace xisrapilx\amysqlo\thread;

use pocketmine\snooze\SleeperNotifier;
use pocketmine\Thread;
use xisrapilx\amysqlo\thread\queue\QueryReceiveQueue;
use xisrapilx\amysqlo\thread\queue\QuerySendQueue;
use xisrapilx\mysqlo\credentials\Credentials;
use xisrapilx\mysqlo\exception\ConnectionException;

class OldThread extends Thread implements MySQLOThread{

    /** @var QuerySendQueue */
    private $querySendQueue;

    /** @var QueryReceiveQueue */
    private $receiveQueue;

    /** @var SleeperNotifier */
    private $queryNotifier;

    /** @var SleeperNotifier */
    private $connectionNotifier;

    /** @var Credentials */
    private $credentials;

    /** @var string Serialized exception */
    private $connectionException;

    /**
     * @param QuerySendQueue $querySendQueue
     * @param QueryReceiveQueue $receiveQueue
     * @param SleeperNotifier $queryNotifier
     * @param SleeperNotifier $connectionNotifier
     * @param Credentials $credentials
     * @param array $additional
     */
    public function __construct(QuerySendQueue $querySendQueue, QueryReceiveQueue $receiveQueue, SleeperNotifier $queryNotifier, SleeperNotifier $connectionNotifier, Credentials $credentials, ...$additional){
        $this->querySendQueue = $querySendQueue;
        $this->receiveQueue = $receiveQueue;
        $this->queryNotifier = $queryNotifier;
        $this->connectionNotifier = $connectionNotifier;
        $this->credentials = $credentials;
    }

    public function run(){
        $this->registerClassLoader();

        try{
            $threadWork = new MySQLThreadWork($this->querySendQueue, $this->receiveQueue, $this->queryNotifier, $this->connectionNotifier, $this->credentials);
            $this->connectionNotifier->wakeupSleeper();
            while(!$this->querySendQueue->isInvalidated()){
                $threadWork->doWork();
            }
        }catch(ConnectionException $connectionException){
            $this->connectionException = serialize([$connectionException->getMessage(), $connectionException->getCode(), $connectionException->getTraceAsString()]);
            $this->connectionNotifier->wakeupSleeper();
        }
    }

    public function getConnectionException() : ?ConnectionException{
        if($this->connectionException !== null){
            $data = unserialize($this->connectionException);
            return (new ConnectionException($data[0], $data[1]))->setStackTraceAsString($data[2]);
        }

        return null;
    }

    public function setGarbage(){
        // NOOP
    }
}