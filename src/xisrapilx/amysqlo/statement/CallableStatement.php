<?php /** @noinspection PhpDocSignatureInspection */

declare(strict_types=1);

namespace xisrapilx\amysqlo\statement;

use xisrapilx\amysqlo\thread\queue\QuerySendQueue;
use xisrapilx\mysqlo\statement\Executable;
use xisrapilx\mysqlo\statement\Statement;

class CallableStatement extends Statement{

    public const MODE_UPDATE = 0x00;
    public const MODE_SELECT = 0x01;
    public const MODE_SELECT_SINGLE = 0x02;
    public const MODE_SELECT_AND_MAP = 0x03;
    public const MODE_SELECT_AND_MAP_SINGLE = 0x04;

    /** @var int */
    protected $id;

    /** @var string */
    protected $query;

    /** @var int */
    protected $mode;

    /** @var QuerySendQueue */
    protected $querySendQueue;

    /** @var callable|null */
    private $onSuccess;

    /** @var callable|null */
    private $onError;

    /**
     * Query constructor.
     *
     * @param int $id
     * @param string $query
     * @param int $mode
     * @param QuerySendQueue $querySendQueue
     * @noinspection PhpMissingParentConstructorInspection
     */
    public function __construct(int $id, string $query, QuerySendQueue $querySendQueue){
        $this->id = $id;
        $this->query = $query;
        $this->querySendQueue = $querySendQueue;
    }

    /**
     * @return callable|null
     */
    public function getOnSuccess() : ?callable{
        return $this->onSuccess;
    }

    /**
     * Signatures:
     * Update: <code>function(int $affectedRows) : void{}</code>
     * Select: <code>function(ResultSet[]|object[]|object[][]|null $result) : void{}</code>
     * SelectSingle: <code>function(ResultSet[]|object|object[]|null $result) : void{}</code>
     * @param callable|null $onSuccess
     *
     * @return $this
     */
    public function setOnSuccess(?callable $onSuccess) : self{
        $this->onSuccess = $onSuccess;

        return $this;
    }

    /**
     * @return callable|null
     */
    public function getOnError() : ?callable{
        return $this->onError;
    }

    /**
     * @param callable|null $onError
     *
     * @return $this
     */
    public function setOnError(?callable $onError) : self{
        $this->onError = $onError;

        return $this;
    }

    /**
     * @return int
     */
    public function getId() : int{
        return $this->id;
    }

    /**
     * @return int
     */
    public function getMode() : int{
        return $this->mode;
    }

    public function getFinalQuery() : string{
        return $this->query;
    }

    /**
     * @see Executable::executeUpdate()
     */
    public function executeUpdate() : void{
        $this->mode = self::MODE_UPDATE;
        $this->querySendQueue->scheduleQuery(
            $this->id,
            $this->mode,
            $this->getFinalQuery()
        );
    }

    /**
     * @see Executable::executeSelect()
     */
    public function executeSelect() : void{
        $this->mode = self::MODE_SELECT;
        $this->querySendQueue->scheduleQuery(
            $this->id,
            $this->mode,
            $this->getFinalQuery()
        );
    }

    /**
     * @see Executable::executeSelectSingle()
     */
    public function executeSelectSingle() : void{
        $this->mode = self::MODE_SELECT_SINGLE;
        $this->querySendQueue->scheduleQuery(
            $this->id,
            $this->mode,
            $this->getFinalQuery()
        );
    }

    /**
     * @see Executable::executeSelectAndMap()
     */
    public function executeSelectAndMap(string $objectToMap, string ...$objectsToMap) : void{
        $this->mode = self::MODE_SELECT_AND_MAP;

        $objectsToMap = array_merge([$objectToMap], $objectsToMap);
        $this->querySendQueue->scheduleQuery(
            $this->id,
            $this->mode,
            $this->getFinalQuery(),
            ...$objectsToMap
        );
    }

    /**
     * @see Executable::executeSelectAndMapSingle()
     */
    public function executeSelectAndMapSingle(string $objectToMap, string ...$objectsToMap) : void{
        $this->mode = self::MODE_SELECT_AND_MAP_SINGLE;

        $objectsToMap = array_merge([$objectToMap], $objectsToMap);
        $this->querySendQueue->scheduleQuery(
            $this->id,
            $this->mode,
            $this->getFinalQuery(),
            ...$objectsToMap
        );
    }
}