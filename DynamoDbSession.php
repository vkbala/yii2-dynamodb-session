<?php
/**
 * @author Balaganesh K <vkbalaganesh@gmail.com>  
 * @package yii2-dynamodbsession
 * @version 1.0
 * @Support Version - Yii-2.0 >=
 */

namespace app\components;

use Yii;
use yii\base\ErrorHandler;
use Aws\DynamoDb\DynamoDbClient;


class DynamoDbSession extends \yii\web\Session
{
    /**
     * @var DynamoDbClient
     */
    public $dynamoDb;

    public $params;

    public $sessionTable = 'sessions';

    /**
     * @var string DynamoDB table name
     */
    private $tableName = 'sessions';

    /**
     * @var string id key name
     */
    public $idColumn = 'id';

    /**
     * @var string data key name
     */
    public $dataColumn="data";

    /**
     * @var string expire key name
     */
    public $expireColumn="expire";

    /**
     * Initializes the Session component.
     * This method will initialize the [[db]] property to make sure it refers to a valid DynamoDB connection.
     * @throws InvalidConfigException if [[db]] is invalid.
     */
    public function init() {        

        $this->dynamoDb = DynamoDbClient::factory([
            'region' => $this->params['region'],
            'key' => $this->params['key'],
            'secret' => $this->params['secret'], //'profile' => 'default',
            'base_url' => isset($this->params['base_url']) ? $this->params['base_url'] : '',
        ]);

        $this->tableName = $this->sessionTable;

        parent::init();        
    }

    /**
     * Returns a value indicating whether to use custom session storage.
     * This method overrides the parent implementation and always returns true.
     * @return boolean whether to use custom storage.
     */
    public function getUseCustomStorage()
    {
        return true;
    }  
    
    /**
     * Updates the current session ID with a newly generated one.
     * Please refer to <http://php.net/session_regenerate_id> for more details.
     * @param boolean $deleteOldSession Whether to delete the old associated session file or not.
     */
    public function regenerateID($deleteOldSession = false)
    {
        $oldId = session_id();

        parent::regenerateID(false);
        $newId = session_id();

        $row = $this->getData($oldId);
        if (!is_null($row)) {
            if ($deleteOldSession) { // Delete + Put = Update
                $this->dynamoDb->deleteItem(array(
                    'TableName' => $this->tableName,
                    'Key' => array(
                        'id' => array('S' => (string) $oldId),
                    ),
                ));
                $this->dynamoDb->putItem(array(
                    'TableName' => $this->tableName,
                    'Item' => array(
                        $this->idColumn => array('S' => (string) $newId),
                        $this->dataColumn => $row[$this->dataColumn],
                        $this->expireColumn => $row[$this->expireColumn],
                    ),
                ));
            } else {
                $row[$this->idColumn] = array('S' => (string) $newId);
                $this->dynamoDb->putItem(array(
                    'TableName' => $this->tableName,
                    'Item' => array($row),
                ));
            }
        } else {
            $this->dynamoDb->putItem(array(
                'TableName' => $this->tableName,
                'Item' => array(
                    $this->idColumn => array('S' => $newId),
                    $this->expireColumn => array('N' => $this->getExpireTime()),
                ),
            ));
        }
    } 

    /**
     * Session read handler.
     * Do not call this method directly.
     * @param string $id session ID
     * @return string the session data
     */
    public function readSession($id)
    {
        $row = $this->getData($id);
        return is_null($row) ? '' : $row[$this->dataColumn]['S'];
    }

    /**
     * Session write handler.
     * Do not call this method directly.
     * @param string $id session ID
     * @param string $data session data
     * @return boolean whether session write is successful
     */
    public function writeSession($id, $data)
    {
        try {
            $this->dynamoDb->putItem(array(
                'TableName' => $this->tableName,
                'Item' => array(
                        $this->idColumn => array('S' => $id),
                        $this->dataColumn => array('S' => $data),
                        $this->expireColumn => array('N' => $this->getExpireTime()),
                ),
            ));
        } catch (\Exception $e) {
            $exception = ErrorHandler::convertExceptionToString($e);
            // its too late to use Yii logging here
            error_log($exception);
            echo $exception;

            return false;
        }            
        return true; //@todo check return from put
    }  

    /**
     * Session destroy handler.
     * Do not call this method directly.
     * @param string $id session ID
     * @return boolean whether session is destroyed successfully
     */
    public function destroySession($id)
    {
        $this->dynamoDb->deleteItem(array(
            'TableName' => $this->tableName,
            'Key' => array(
                'id' => array('S' => (string) $id),
            ),
        ));

        return true; //@todo check return from put
    }

    /**
     * Session GC (garbage collection) handler.
     * Do not call this method directly.
     * @param integer $maxLifetime The number of seconds after which data will be seen as 'garbage' and cleaned up.
     * @return boolean whether session is GCed successfully
     */
    public function gcSession($maxLifetime)
    {
        //@TODO
        return true; //@todo check return from put
    }          

    public function getData($id)
    {

        $r = $this->dynamoDb->getItem(array(
            'ConsistentRead' => true,
            'TableName' => $this->tableName,
            'Key' => array(
                'id' => array('S' => (string) $id),
            ),
        ));

        return $r['Item'];
    }

    protected function getExpireTime()
    {
        return time() + $this->getTimeout();
    }    
}
