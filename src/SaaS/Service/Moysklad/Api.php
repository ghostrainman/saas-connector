<?php

/**
 * PHP version 5.6
 *
 * @category ApiClient
 * @package  SaaS\Service\Moysklad
 * @author   Andrey Artahanov <azgalot9@gmail.com>
 * @license  http://opensource.org/licenses/MIT MIT License
 * @link     http://github.com/gwinn/saas-connector
 * @see      https://online.moysklad.ru/api/remap/1.1/doc/index.html
 */

namespace SaaS\Service\Moysklad;

use SaaS\Http\Response;

/**
 * MoySklad API Client
 *
 * @category ApiClient
 * @package  SaaS\Service\Moysklad
 * @author   Andrey Artahanov <azgalot9@gmail.com>
 * @license  http://opensource.org/licenses/MIT MIT License
 * @link     http://github.com/gwinn/saas-connector
 * @see      https://online.moysklad.ru/api/remap/1.1/doc/index.html
 */
class Api
{
    /**
     * Requests
     */
    const REQUEST_ATTRIBUTES_MAIN = array('metadata', 'all', 'bystore', 'byoperation');
    const REQUEST_ATTRIBUTES_SECOND = array(
        'accounts',
        'contactpersons',
        'packs',
        'cashiers',
        'positions'
    );

    /**
     * JsonAPI client МойСклад
     * @var client
     * @access protected
     */
    protected $client;

    /**
     * Entity mapping
     * @var entity
     * @access protected
     */
    protected $entity = array(
        "counterparty" => "entity",
        "consignment" => "entity",
        "currency" => "entity",
        "productFolder" => "entity",
        "service" => "entity",
        "product" => "entity",
        "contract" => "entity",
        "variant" => "entity",
        "project" => "entity",
        "state" => "entity",
        "employee" => "entity",
        "store" => "entity",
        "organization" => "entity",
        "retailshift" => "entity",
        "retailstore" => "entity",
        "cashier" => "entity",
        "customerOrder" => "entity",
        "demand" => "entity",
        "invoiceout" => "entity",
        "retaildemand" => "entity",
        "purchaseOrder" => "entity",
        "supply" => "entity",
        "invoicein" => "entity",
        "paymentin" => "entity",
        "paymentout" => "entity",
        "cashin" => "entity",
        "cashout" => "entity",
        "companysettings" => "entity",
        "expenseItem" => "entity",
        "country" => "entity",
        "uom" => "entity",
        "customentity" => "entity",
        "salesreturn" => "entity",
        "purchasereturn" => "entity",
        "stock" => "report",
        "assortment" => "pos",
        "openshift" => "pos",
        "closeshift" => "pos",
        "webhook" => "entity"
    );

    /**
     * Filters for get data requests
     * @var main_filters
     * @access protected
     */
    protected $main_filters = array(
        "updatedFrom",
        "updatedTo",
        "updatedBy",
        "state.name",
        "state.id",
        "organization.id",
        "search",
        "isDeleted",
        "limit",
        "offset",
        "filters"
    );

    /**
     * Constructor
     *
     * @param string $login
     * @param string $password
     *
     * @access public
     *
     * @return void
     */
    public function __construct($login, $password)
    {
        $this->client = new Request($login, $password);
    }

    /**
     * Get data.
     *
     * @param array $params
     * @param array $filters
     *
     * @throws \InvalidArgumentException
     *
     * @access public
     *
     * @return ApiResponse
     */
    public function getData(
        $params,
        $filters = array()
    )
    {
        if (empty($params) || is_null($params)) {
            throw new \InvalidArgumentException('The `params` can not be empty');
        }

        if (!is_array($params)) {
            throw new \InvalidArgumentException('Wrong `params` type: `params` must be an "array"');
        }

        if (gettype(reset($params)) !== 'string') {
            throw new \InvalidArgumentException('Wrong `type`: `type` must be a "string"');
        }

        if (empty($this->entity[reset($params)])) {
            throw new \InvalidArgumentException('Undefined data type');
        }

        $filter = array();

        if (count($params) > 4) {
            throw new \InvalidArgumentException('Too many attribute...');
        }

        switch (count($params)) {
            case 1:
            case 2:
                if (!empty($filters)) {
                    if (is_array($filters)) {
                        if (!empty(array_diff(array_keys($filters), $this->main_filters))) {
                            throw new \InvalidArgumentException(
                                sprintf(
                                    'Wrong filters: `%s`',
                                    implode(', ', array_diff(array_keys($filters), $this->main_filters))
                                )
                            );
                        }
                        foreach ($filters as $index=>$value) {
                            $filter[$index] = $value;
                        }
                        unset($index, $value);
                    } else {
                        throw new \InvalidArgumentException('Wrong `filters` type: `filters` must be an "array"');
                    }
                }
                break;
            case 3:
            case 4:
                $this->checkUuid($params[1]);

                if (gettype($params[2]) !== 'string') {
                    throw new \InvalidArgumentException('Wrong second attribute: attribute must be a "string"');
                }

                if (!in_array($params[2], self::REQUEST_ATTRIBUTES_SECOND)) {
                    throw new \InvalidArgumentException(sprintf('Wrong attribute: `%s`', $params[2]));
                }
                break;
        }

        switch (count($params)) {
            case 2:
                if (!in_array($params[1], self::REQUEST_ATTRIBUTES_MAIN)) {
                    $this->checkUuid($params[1]);
                }
                break;
            case 4:
                $this->checkUuid($params[3]);
                break;
        }

        $uri = $this->entity[reset($params)] . '/';

        foreach ($params as $param) {
            $uri .= $param . '/';
        }
        unset($param);
        $uri = trim($uri, '/');

        return $this->client->makeRequest(
            $uri,
            Request::METHOD_GET,
            $filter
        );
    }

    /**
     * Create data.
     *
     * @param mixed $param
     * @param array $data
     *
     * @access public
     *
     * @return Response
     */
    public function createData($param, $data)
    {
        if (empty($data)) {
            throw new \InvalidArgumentException('The `data` can not be empty');
        }

        if (!is_array($data)) {
            throw new \InvalidArgumentException('Wrong `data` type: `data` must be an "array"');
        }

        if (is_array($param) && count($param) == 2) {
            $type = $param[0];
            $uuid = $param[1];
            $this->checkUuid($uuid);
        } else {
            $type = $param;
            $uuid = null;
        }

        if (gettype($type) !== 'string') {
            throw new \InvalidArgumentException('Wrong `type`: `type` must be a "string"');
        }

        if (empty($type) || is_null($type)) {
            throw new \InvalidArgumentException('The `param` can not be empty');
        }

        if (empty($this->entity[$type])) {
            throw new \InvalidArgumentException('Undefined data type');
        }

        $parameters['data'] = $data;

        return $this->client->makeRequest(
            $this->entity[$type] . '/' . $type . (!is_null($uuid) ? ('/'.$uuid) : ''),
            Request::METHOD_POST,
            $parameters
        );
    }

    /**
     * Update data.
     *
     * @param string $type
     * @param string $uuid
     * @param json $data
     *
     * @access public
     *
     * @return Response
     */
    public function updateData($type, $uuid, $data)
    {
        if (empty($type) || is_null($type)) {
            throw new \InvalidArgumentException('The `type` can not be empty');
        }

        if (gettype($type) !== 'string') {
            throw new \InvalidArgumentException('Wrong `type`: `type` must be a "string"');
        }

        if (empty($this->entity[$type])) {
            throw new \InvalidArgumentException('Undefined data type');
        }

        $this->checkUuid($uuid);

        if (empty($data)) {
            throw new \InvalidArgumentException('The `data` can not be empty');
        }

        if (!is_array($data)) {
            throw new \InvalidArgumentException('Wrong `data` type: `data` must be an "array"');
        }

        $parameters['data'] = $data;

        return $this->client->makeRequest(
            sprintf($this->entity[$type] . '/' . $type . '/%s', $uuid),
            Request::METHOD_PUT,
            $parameters
        );
    }

    /**
     * Delete data.
     *
     * @param string $type
     * @param string $uuid
     *
     * @access public
     *
     * @return Response
     */
    public function deleteData($type, $uuid)
    {
        if (empty($type) || is_null($type)) {
            throw new \InvalidArgumentException('The `type` can not be empty');
        }

        if (gettype($type) !== 'string') {
            throw new \InvalidArgumentException('Wrong `type`: `type` must be a "string"');
        }

        if (empty($this->entity[$type])) {
            throw new \InvalidArgumentException('Undefined data type');
        }

        $this->checkUuid($uuid);

        return $this->client->makeRequest(
            sprintf($this->entity[$type] . '/' . $type . '/%s', $uuid),
            Request::METHOD_DELETE
        );
    }

    /**
     * Check uuid.
     *
     * @param string $uuid
     * @throws InvalidArgumentException
     *
     * @access private
     *
     * @return void
     */
    private function checkUuid($uuid)
    {
        if (is_null($uuid) || empty($uuid)) {
            throw new \InvalidArgumentException('The `uuid` can not be empty');
        }

        if (gettype($uuid) !== 'string') {
            throw new \InvalidArgumentException('Wrong `uuid` type: `uuid` must be a "string"');
        }

        if (!preg_match("#^[\w\d]{8}-[\w\d]{4}-[\w\d]{4}-[\w\d]{4}-[\w\d]{12}$#", $uuid)) {
            if (preg_match("#^[a-z\d]+$#i", $uuid)) {
                throw new \InvalidArgumentException(sprintf('Wrong attribute: `%s`', $uuid));
            }
            throw new \InvalidArgumentException('The `uuid` has invalid format');
        }
    }
}