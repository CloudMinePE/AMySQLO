<?php

declare(strict_types=1);

namespace xisrapilx\amysqlo\thread\queue;

use Threaded;
use xisrapilx\mysqlo\statement\Statement;

/**
 * Class QuerySendQueue
 * @package xisrapilx\amysqlo\thread\queue
 *
 * Original: https://github.com/poggit/libasynql/
 */
class QuerySendQueue extends Threaded{

    /** @var bool */
    private $invalidated = false;

    /** @var Threaded */
    private $queries;

    public function __construct(){
        $this->queries = new Threaded();
    }

    /**
     * @param int $queryId
     * @param int $mode
     * @param string $query
     * @param string ...$objectsToMap
     */
    public function scheduleQuery(int $queryId, int $mode, $query, string ...$objectsToMap) : void{
        $this->synchronized(function() use ($queryId, $mode, $query, $objectsToMap) : void{
            $this->queries[] = serialize([$queryId, $mode, $query, $objectsToMap]);
            $this->notifyOne();
        });
    }

    /**
     * @return array|null
     */
    public function fetchQuery() : ?array {
        return $this->synchronized(function(): ?array {
            while($this->queries->count() === 0 && !$this->isInvalidated()){
                $this->wait();
            }

            $query = $this->queries->shift();
            if($query !== null){
                $query = unserialize($query);

                return $query;
            }

            return null;
        });
    }

    public function invalidate() : void {
        $this->synchronized(function():void{
            $this->invalidated = true;
            $this->notify();
        });
    }

    /**
     * @return bool
     */
    public function isInvalidated(): bool {
        return $this->invalidated;
    }

    public function setGarbage(){
        parent::setGarbage();
    }
}