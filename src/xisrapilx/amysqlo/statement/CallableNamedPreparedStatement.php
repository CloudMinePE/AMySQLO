<?php

declare(strict_types=1);

namespace xisrapilx\amysqlo\statement;

use xisrapilx\amysqlo\thread\queue\QuerySendQueue;

class CallableNamedPreparedStatement extends CallableStatement{

    private $params = [];

    /**
     * NamedPreparedCallableStatement constructor.
     * @param int $id
     * @param string $query
     * @param QuerySendQueue $querySendQueue
     */
    public function __construct(int $id, string $query, QuerySendQueue $querySendQueue){
        parent::__construct($id, $query, $querySendQueue);
    }

    /**
     * @param string $name
     * @param string $value
     * @param bool $escape
     *
     * @return CallableNamedPreparedStatement
     */
    public function setString(string $name, string $value, bool $escape = true) : self{
        $this->params[":".$name] = $escape ? $this->escapeString($value) : $value;

        return $this;
    }

    /**
     * @url https://stackoverflow.com/questions/1162491/alternative-to-mysql-real-escape-string-without-connecting-to-db
     *
     * @param string $value
     *
     * @return string
     */
    private function escapeString(string $value) : string{
        $return = '';
        for($i = 0; $i < strlen($value); ++$i) {
            $char = $value[$i];
            $ord = ord($char);
            if($char !== "'" && $char !== "\"" && $char !== '\\' && $ord >= 32 && $ord <= 126)
                $return .= $char;
            else
                $return .= '\\x' . dechex($ord);
        }
        return $return;
    }

    /**
     * @param string $name
     * @param int $value
     *
     * @return CallableNamedPreparedStatement
     */
    public function setInt(string $name, int $value) : self{
        $this->params[":".$name] = $value;

        return $this;
    }

    /**
     * @param string $name
     * @param float $value
     *
     * @return CallableNamedPreparedStatement
     */
    public function setFloat(string $name, float $value) : self{
        $this->params[":".$name] = $value;

        return $this;
    }

    /**
     * @param string $name
     * @param bool $value
     *
     * @return CallableNamedPreparedStatement
     */
    public function setBoolean(string $name, bool $value) : self{
        $this->params[":".$name] = "b'".($value ? 1 : 0)."'";

        return $this;
    }

    /**
     * @return string
     */
    public function getFinalQuery() : string{
        return str_replace(array_keys($this->params), array_values($this->params), $this->query);
    }
}