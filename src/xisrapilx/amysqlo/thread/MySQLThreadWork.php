<?php

declare(strict_types=1);

namespace xisrapilx\amysqlo\thread;

use pocketmine\snooze\SleeperNotifier;
use xisrapilx\amysqlo\statement\CallableStatement;
use xisrapilx\amysqlo\thread\queue\QueryReceiveQueue;
use xisrapilx\amysqlo\thread\queue\QuerySendQueue;
use xisrapilx\mysqlo\credentials\Credentials;
use xisrapilx\mysqlo\exception\ConnectionException;
use xisrapilx\mysqlo\exception\QueryException;
use xisrapilx\mysqlo\MySQL;

final class MySQLThreadWork{

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

    /** @var MySQL */
    private $mysql;

    /**
     * @param QuerySendQueue $querySendQueue
     * @param QueryReceiveQueue $receiveQueue
     * @param SleeperNotifier $queryNotifier
     * @param SleeperNotifier $connectionNotifier
     * @param Credentials $credentials
     * @throws ConnectionException
     */
    public function __construct(QuerySendQueue $querySendQueue, QueryReceiveQueue $receiveQueue, SleeperNotifier $queryNotifier, SleeperNotifier $connectionNotifier, Credentials $credentials){
        $this->querySendQueue = $querySendQueue;
        $this->receiveQueue = $receiveQueue;
        $this->queryNotifier = $queryNotifier;
        $this->connectionNotifier = $connectionNotifier;
        $this->credentials = $credentials;

        $this->mysql = new MySQL($credentials);
        $this->mysql->connect();
    }

    public function doWork(){
        $queryData = $this->querySendQueue->fetchQuery();

        if(is_array($queryData)){
            $queryId = array_shift($queryData);
            $queryMode = array_shift($queryData);
            $query = array_shift($queryData);
            $objectsToMap = array_shift($queryData);

            $result = null;
            try{
                switch($queryMode){
                    case CallableStatement::MODE_UPDATE:
                        $result = $this->mysql->executeUpdate($query);
                        break;

                    case CallableStatement::MODE_SELECT:
                        $result = $this->mysql->executeSelect($query);
                        break;

                    case CallableStatement::MODE_SELECT_SINGLE:
                        $result = $this->mysql->executeSelectSingle($query);
                        break;

                    case CallableStatement::MODE_SELECT_AND_MAP:
                        $result = $this->mysql->executeSelectAndMap($query, ...$objectsToMap);
                        break;

                    case CallableStatement::MODE_SELECT_AND_MAP_SINGLE:
                        $result = $this->mysql->executeSelectAndMapSingle($query, ...$objectsToMap);
                        break;
                }

                $this->receiveQueue->publishResult($queryId, $result);
                $this->queryNotifier->wakeupSleeper();
            }catch(QueryException $queryException){
                $this->receiveQueue->publishError($queryId, $queryException);
                $this->queryNotifier->wakeupSleeper();
            }
        }
    }
}