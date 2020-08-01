<?php

declare(strict_types=1);

namespace xisrapilx\amysqlo\thread\queue;

use Threaded;
use xisrapilx\mysqlo\exception\MySQLOException;
use xisrapilx\mysqlo\result\ResultSet;

/**
 * Class QueryReceiveQueue
 * @package xisrapilx\amysqlo\thread\queue
 *
 * Original: https://github.com/poggit/libasynql/
 */
class QueryReceiveQueue extends Threaded{

    /**
     * @param int $queryId
     * @param ResultSet[]|object[]|object[][]|int $result
     */
    public function publishResult(int $queryId, $result) : void{
        $this[] = serialize([$queryId, $result]);
    }

    /**
     * @param int $queryId
     * @param MySQLOException $error
     */
    public function publishError(int $queryId, MySQLOException $error){
        $this[] = serialize([$queryId, $error]);
    }

    /**
     * @param int $queryId
     * @param ResultSet[]|object[]|object[][]|int|MySQLOException $result
     *
     * @return bool
     */
    public function fetchResult(int &$queryId, &$result) : bool{
        $row = $this->shift();
        if(is_string($row)){
            [$queryId, $result] = unserialize($row, ["allowed_classes" => true]);

            return true;
        }

        return false;
    }

    public function setGarbage(){
        // NOOP
    }
}