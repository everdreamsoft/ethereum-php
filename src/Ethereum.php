<?php
namespace Ethereum;

use Graze\GuzzleHttp\JsonRpc\Client as RpcClient;

use Ethereum\DataType\EthDataType;
use Exception;
use Ethereum\DataType\EthD;
use Ethereum\DataType\EthD32;
use Ethereum\EcRecover;
use Ethereum\DataType\FilterChange;

/**
 * @defgroup client Ethereum Web3 Client
 *
 * %Ethereum Web3 (JsonRPC) client.
 */

/**
 * %Ethereum Web3 API for PHP.
 *
 * @page ethClient Web3 Client
 *
 * Ethereum::Ethereum is the starting point to communicate with any %Ethereum client (like [Geth](https://geth.ethereum.org/), [Parity](https://github.com/paritytech/parity/releases/tag/v1.8.3), [TestRPC](https://github.com/trufflesuite/ganache-cli), [Quorum](https://www.jpmorgan.com/global/Quorum) ...).
 *
 * Implements %Ethereum Web3 JsonRPC API for PHP. Read more about it at the [Ethereum-wiki](https://github.com/ethereum/wiki/wiki/JSON-RPC).
 *
 * To get started you might check the hierarchical [class list](hierarchy.html) to get an easy overview about the available Data structures.
 *
 * Web3Interface
 *
 */

/**
 * Ethereum Web3 API for PHP.
 *
 * @ingroup client
 */
class Ethereum extends EthereumStatic implements Web3Interface
{

    // Using a trait to enable Tools like PHP storm and doxygen understand PHP magic method calls.
    use Web3Methods;

    private $definition;
    private $methods;
    private $id = 0;
    public $client;
    public $debugHtml = '';


    /**
     * Constructing Ethereum Class.
     *
     * Remarks:
     * Ethereum class is based on ethjs-schema.json file.
     * - Everything is typed. All data types extend EthDataType class.
     * - Primitive data have a mostly common interface. See EthD.
     * - Complex data types (See "objects" in schema.json) are generated by
     *   script. This guarantees consistent type handling. Changes to the
     *   generated need to be implemented in make_datatypes.php.
     * - Methods are typed based on ethjs-schema and implemented as Closure calls,
     *   which makes this constructor complex, but ensures consistent typing.
     *
     * @param string $url
     *   Connection to Ethereum node. E.g:
     *   http://localhost:8545 or https://mainnet.infura.io/drupal.
     */
    public function __construct(string $url = 'http://localhost:8545')
    {
      // Require the workaround helpers, as autoload files in composer
      //   doesn't work as expected.
      require_once __DIR__ . '/helpers/ethereum-client-workaround-helpers.php';

      $this->client = RpcClient::factory($url, [
            // Debug JsonRPC requests.
            'debug'     => false,
            'rpc_error' => true,
        ]);

        $this->definition = self::getDefinition();

        foreach ($this->definition['methods'] as $name => $params) {
            ${$name} = function () {

                $request_params = [];

                // Get name of called function.
                $method = debug_backtrace()[2]['args'][0];
                $this->debug('Called function name', $method);

                // Get call and return parameters and types.
                $param_definition = $this->definition['methods'][$method];

                // Arguments send with function call.
                $valid_arguments = $param_definition[0];
                $argument_class_names = [];
                if (count($valid_arguments)) {
                    $this->debug('Valid arguments', $valid_arguments);

                    // Get argument definition Classes.
                    foreach ($valid_arguments as $type) {
                        $primitiveType = EthD::typeMap($type);
                        if ($primitiveType) {
                            $argument_class_names[] = $primitiveType;
                        } else {
                            $argument_class_names[] = $type;
                        }
                    }
                    $this->debug('Valid arguments class names', $argument_class_names);
                }

                // Arguments send with function call.
                $args = func_get_args();
                if (count($args) && isset($argument_class_names)) {
                    $this->debug('Arguments', $args);

                    // Validate arguments.
                    foreach ($args as $i => $arg) {
                        /* @var EthDataType $arg */

                        if (is_subclass_of ($arg,'Ethereum\DataType\EthDataType')) {
                            // Former $arg->getType has been removed.
                            // Getting the basename of the class.
                            $argType = basename(str_replace('\\', '/', get_class($arg)));
                            if ($argument_class_names[$i] !== $argType) {
                                throw new \InvalidArgumentException("Argument $i is "
                                  . $argType
                                  . " but expected $argument_class_names[$i] in $method().");
                                }
                                else {
                                // Add value. Inconsistently booleans are not hexEncoded if they
                                // are not data like in eth_getBlockByHash().
                                if ($arg->isPrimitive() && $argType !== 'EthB') {

                                    if ($method === 'eth_getFilterChanges') {
                                        // Filter Id is an un-padded value :(
                                        $request_params[] = $arg->hexValUnpadded();
                                    }
                                    else {
                                        $request_params[] = $arg->hexVal();
                                    }
                                }
                                elseif ($arg->isPrimitive() && $argType === 'EthB') {
                                    $request_params[] = $arg->val();
                                }
                                else {
                                    $request_params[] = $arg->toArray();
                                }
                            }
                      }
                      else
                      {
                          throw new \InvalidArgumentException('Arg ' . $i . ' is not a EthDataType.');
                      }
                    }
                }

                // Validate required parameters.
                if (isset($param_definition[2])) {
                    $required_params = array_slice($param_definition[0], 0, $param_definition[2]);
                    $this->debug('Required Params', $required_params);
                }

                if (isset($required_params) && count($required_params)) {
                    foreach ($required_params as $i => $param) {
                        if (!isset($request_params[$i])) {
                            throw new \InvalidArgumentException("Required argument $i $argument_class_names[$i] is missing in $method().");
                        }
                    }
                }

                // Default block parameter required for function call.
                // See: https://github.com/ethereum/wiki/wiki/JSON-RPC#the-default-block-parameter.
                $require_default_block = false;
                if (isset($param_definition[3])) {
                    $require_default_block = $param_definition[3];
                    $this->debug('Require default block parameter', $require_default_block);
                }
                if ($require_default_block) {
                    $arg_is_set = false;
                    foreach ($argument_class_names as $i => $class) {
                        if ($class === 'EthBlockParam' && !isset($request_params[$i])) {
                            $request_params[$i] = 'latest';
                        }
                    }
                }

                // Return type.
                $return_type = $param_definition[1];
                $this->debug('Return value type', $return_type);

                $is_primitive = (is_array($return_type)) ? (bool)EthD::typeMap($return_type[0]) : (bool)EthD::typeMap($return_type);

                if (is_array($return_type)) {
                    $return_type_class = '[' . EthD::typeMap($return_type[0]) . ']';
                } elseif ($is_primitive) {
                    $return_type_class = EthD::typeMap($return_type);
                } else {
                    // Return Complex type.
                    $return_type_class = $return_type;
                }
                $this->debug('Return value Class name ', $return_type_class);

                // Call.
                $this->debug('Final request params', $request_params);
                $value = $this->etherRequest($method, $request_params);

                // Fix client specific flaws in src/helpers/helpers.php.
                $functionName = 'eth_workaround_' . $method;
                if (function_exists($functionName)) {
                    $value = call_user_func($functionName, $value);
                }

                $return = $this->createReturnValue($value, $return_type_class, $method);
                $this->debug('Final return object', $return);
                $this->debug('<hr />');

                return $return;
            };
            // Binding above function.
            $this->methods[$name] = \Closure::bind(${$name}, $this, get_class());
        }
    }


    /**
     * Method call wrapper.
     *
     * @param string $method
     * @param array $args
     * @return mixed
     */
    public function __call(string $method, array $args)
    {
        if (is_callable($this->methods[$method])) {
            return call_user_func_array($this->methods[$method], $args);
        } else {
            throw new \InvalidArgumentException('Unknown Method: ' . $method);
        }
    }


    /**
     * Handle Return Value.
     *
     * @param string|array $value
     *   Returned value from JsonRPC request.
     * @param string $return_type_class
     *   Class name of the expected return type.
     * @param string $method
     *   Method name for error messages.
     *
     * * @return array|null|object Expected object.
     * Expected object.
     *
     * @throws \Exception
     */
    private function createReturnValue($value, string $return_type_class, string $method)
    {
        $return = null;

        if (is_null($value)) {
            return null;
        }

        // Get return value type.
        $class_name = '\\Ethereum\\DataType\\' . EthDataType::getTypeClass($return_type_class);
        // Is array ?
        $array_val = $this->isArrayType($return_type_class);
        // Is primitive data type?
        $is_primitive = $class_name::isPrimitive();

        // Primitive array Values.
        if ($is_primitive && $array_val && is_array($value)) {
            // According to schema array returns will always have primitive values.
            $return = $this->valueArray($value, $class_name);
        } elseif ($is_primitive && !$array_val && !is_array($value)) {
            $return = new $class_name($value);
        }

        // Complex array types.
        if (!$is_primitive && !$array_val && is_array($value)) {
            $return = $this->arrayToComplexType($class_name, $value);
        }
        elseif (!$is_primitive) {

            if ($array_val) {
                if ($method === 'eth_getFilterChanges') {
                    // Only be [FilterChange| D32]
                    $return = $this->handleFilterChangeValues($value);
                }
                elseif ($method === 'shh_getFilterChanges') {
                    throw new \Exception('shh_getFilterChanges not implemented.');
                }
                else {
                    // There only should be [SSHFilterChange] left
                    throw new \Exception(' Return is a array of non primitive types. Method: ' . $method);
                }
            }
            else {
                $return = new $class_name();
            }
        }

        if (!$return && !is_array($return)) {
            throw new Exception('Expected '
                                 . $return_type_class
                                 . ' at '
                                 . $method
                                 . ' (), couldn not be decoded. Value was: '
                                 . print_r($value, true));
        }
        return $return;
    }


  /**
   *
   * Note if there are still Arrays passed here we crash.
   * Array types should be insicated by "[TYPE]" or type[].
   *
   * @param string $type
   * @return bool
   */
    protected static function isArrayType(string $type) {
      return (strpos($type, '[') !== FALSE );
    }

    /**
     * Request().
     *
     * @param string $method
     *   JsonRPC method to be called.
     * @param array  $params
     *   Request parameters. See Guzzle doc.
     * @return mixed
     */
    public function request(string $method, array $params = [])
    {
        $this->id++;
        return $this->client->send($this->client->request($this->id, $method, $params))->getRpcResult();
    }


    /**
     * Ethereum request.
     *
     * @param string $method
     * @param array $params
     *
     * @throws \Exception
     *
     * @return array|mixed
     */
    public function etherRequest(string $method, array $params = [])
    {
        try {
            //Shaban fix bug with hex starting with 0x0
            if ($method == 'eth_getBlockByNumber'){
                while (substr( $params[0], 0, 3 ) === "0x0") {

                    $params[0]  = str_replace("0x0", "0x", $params[0]);
                }
            }

            return $this->request($method, $params);
        } catch (\Exception $e) {
            if ($e->getCode() === 405) {
                return [
                    'error'   => true,
                    'code'    => 405,
                    'message' => $e->getMessage(),
                ];
            } else {
                throw $e;
            }
        }
    }


    /**
     * Debug Helper.
     *
     * @param string $title
     *   Any HTML. Will be printed bold.
     * @param string|object|array $content
     *   Content will be printed in appropriate format.
     *
     * @return string
     *   Debug HTML output.
     */
    public function debug(string $title, $content = null)
    {
        $return = '';
        $return .= '<p style="margin-left: 1em"><b>' . $title . "</b></p>";
        if ($content) {
            $return .= '<pre style="background: rgba(0,0,0, .1); margin: .5em; padding: .25em; ">';
            if (is_object($content) || is_array($content)) {
                ob_start();
                var_dump($content);
                $return .= ob_get_clean();
            } else {
                $return .= ($content);
            }
            $return .= "</pre>";
        }
        $this->debugHtml .= $return;

        return $return;
    }


    /**
     * handleFilterChangeValues().
     *
     * The return values for getFilterChange are not consistently implemented
     * in the schema. @see https://github.com/ethjs/ethjs-schema/issues/10
     *
     * See also https://github.com/ethereum/wiki/wiki/JSON-RPC#returns-42
     *
     * @param $values array
     *
     * @throws \Exception
     *
     * @return array
     */
    protected static function handleFilterChangeValues(array $values) {

        $return = [];
        foreach ($values as $val) {
            // If $val is an array, we handle a change of eth_newFilter -> returns [FilterChange]
            if (is_array($val)) {
                $processed = [];
                foreach (FilterChange::getTypeArray() as $key => $type) {

                    if (substr($type, 0, 1) === '[') {
                        // param is an array. E.g topics.
                        $className = '\Ethereum\Datatype\\' . str_replace(['[', ']'], '', $type);
                        $sub = [];
                        foreach ($val[$key] as $subVal) {
                            $sub[] = new $className($subVal);
                        }
                        $processed[] = $sub;

                        // @todo We'll need to decode the ABI of the values too!
                    }
                    else {
                        $className = '\Ethereum\Datatype\\' . $type;
                        $processed[] = isset($val[$key]) ? new $className($val[$key]) : null;
                    }
                }
                $return[] = new FilterChange(...$processed);
            }
            else {
                // If $val not an array, we handle a change of
                // eth_newBlockFilter (block hashes) -> returns [D32] or
                // eth_newPendingTransactionFilter (transaction hashes) -> returns [D32]
                $return[] = new EthD32($val);
            }

        }
        return $return;
    }


  /**
   * Determine type class name for primitive and complex data types.
   *
   * @param string $class_name
   *   Type to construct from associative Array.
   * @param array  $values
   *   Associative value array.
   *
   * @throws Exception
   *   If something is wrong.
   *
   * @return object|array
   *   Object of type $class_name.
   */
    protected static function arrayToComplexType(string $class_name, array $values)
    {
        $return = [];
        $class_values = [];
        if (!substr($class_name, 1,8) === __NAMESPACE__) {
            $class_name = __NAMESPACE__  . "\\$class_name";
        }

        // Applying workarounds.
        $functionName = 'eth_workaround_' . strtolower(str_replace('\\', '_', substr($class_name,1)));
        if (function_exists($functionName)) {
            $values = call_user_func($functionName, $values);
        }


        /** @var  $class_name EthD or a derived class. */
        $type_map = $class_name::getTypeArray();

        // Looping through the values of expected of $class_name.
        foreach ($type_map as $name => $val_class) {

            if (isset($values[$name])) {
                $val_class = '\\Ethereum\\DataType\\' . EthDataType::getTypeClass($val_class);

                // We might expect an array like logs=[FilterChange].
                if (is_array($values[$name])) {
                    $sub_values = [];
                    foreach ($values[$name] as $sub_val) {

                        // Working around the "DATA|Transaction" type in Blocks.
                        // This is a weired Ethereum problem. In eth_getBlockByHash
                        // and eth_getblockbynumber return type depends on the
                        // second param.
                        // @See: https://github.com/ethereum/wiki/wiki/JSON-RPC#eth_getblockbynumber.
                        if (!is_array($sub_val) && $name === 'transactions') {
                          $val_class = '\\Ethereum\\DataType\\EthD32';
                        }

                        // Work around testrpc giving not back an array.
                        if (is_array($sub_val)) {
                            $sub_values[] = self::arrayToComplexType($val_class, $sub_val);
                        }
                        else {
                            $sub_values[] = new $val_class($sub_val);
                        }
                    }
                    $class_values[] = $sub_values;
                }
                else {
                    $class_values[] = new $val_class($values[$name]);
                }
            }
            else {
                // In order to create a proper constructor null values are required.
                $class_values[] = null;
            }
        }
        $return = new $class_name(...$class_values);
    if (!$return && !is_array($return)) {
      throw new \Exception('Expected ' . $return_type_class . ' at ' . $method . ' (), couldn not be decoded. Value was: ' . print_r($value, TRUE));
    }
    return $return;
  }


  /**
   * PersonalEcRecover function.
   *
   * @param string $message
   *   UTF-8 text.
   *
   * @param EthD $signature
   *   Hex value of the Message Signature.
   *
   * @throws Exception
   *
   * @return string EthereumAddress with prefix.
   */
  public static function personalEcRecover(string $message, EthD $signature) {
    return EcRecover::personalEcRecover($message, $signature->hexVal());
  }


  /**
   * Create value array.
   *
   * Turns a array('0x56789...', ...) into array(EthD32(0x56789...), ...)
   *
   * @param array  $values
   *   Array of values of a unique data type.
   * @param string $typeClass
   *   Class name for the data type.
   *
   * @throws Exception
   *
   * @return array
   *   Array of value objects of the given type.
   */
    public static function valueArray(array $values, string $typeClass)
    {
        $return = [];
        if (!class_exists($typeClass)) {
            $typeClass = '\\' . __NAMESPACE__  . '\\DataType\\' . $typeClass;
        }
        foreach ($values as $i => $val) {
            if (is_object($val)) {
                $return[$i] = $val->toArray();
            }
            if (is_array($val)) {
                $return[$i] = self::arrayToComplexType($typeClass, $val);
            }
            $return[$i] = new $typeClass($val);
        }
        return $return;
    }


}
