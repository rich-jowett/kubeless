<?php

namespace Kubeless;

use Kubeless\Exception\HttpException;
use Kubeless\Exception\FunctionTimeoutException;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

class Controller
{
    private $app;
    private $timeout;
    private $functionName;
    private $functionContext;

    /**
     * Controller constructor.
     *
     * @param int $timeout
     * @param string $functionName
     * @param string $functionHandler
     * @param string $functionPath
     * @param string $functionRuntime
     * @param int $functionMemoryLimit
     */
    public function __construct(
        int $timeout,
        string $functionName,
        string $functionHandler,
        string $functionPath,
        string $functionRuntime,
        int $functionMemoryLimit
    ) {
        $this->timeout = $timeout;
        $this->functionName = $functionName;

        $filePath = sprintf(
            '%s/%s.php',
            $functionPath,
            $functionHandler
        );

        require_once $filePath;

        $this->app = new \Slim\App();
        $this->functionContext = (object) [
            'function-name' => $this->functionName,
            'timeout' => $this->timeout,
            'runtime' => $functionRuntime,
            'memory-limit' => $functionMemoryLimit,
        ];
    }


    /**
     * Execute the injected function.
     *
     * @param Request $request
     * @param Response $response
     * @return  Response
     * @throws \Exception
     * @throws FunctionTimeoutException
     */
    private function runFunction(Request $request, Response $response): Response
    {
        set_time_limit($this->timeout);

        if (!function_exists($this->functionName)) {
            throw new \Exception(sprintf("Function %s not exist", $this->functionName));
        }

        $pid = pcntl_fork();

        switch ($pid) {
            case 0:
                error_reporting(0);
                $data = $request->getBody()->getContents();
                if ($_SERVER['HTTP_CONTENT_TYPE'] == 'application/json') {
                    $data = json_decode($data);
                }
                $event = (object) array(
                    'data' => $data,
                    'event-type' => $_SERVER['HTTP_EVENT_TYPE'],
                    'event-id' => $_SERVER['HTTP_EVENT_ID'],
                    'event-time' => $_SERVER['HTTP_EVENT_TIME'],
                    'event-namespace' => $_SERVER['HTTP_EVENT_NAMESPACE'],
                    'extensions' => (object) array(
                        'request' => $request,
                        'response' => $response,
                    )
                );
                return call_user_func($this->functionName, $event, $this->functionContext);

            case -1:
                throw new FunctionTimeoutException();

            default:
                sleep($this->timeout);
                posix_kill($pid, SIGKILL);
        }

        return $response;
    }


    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     * @throws \Exception
     */
    public function root(Request $request, Response $response, array $args)
    {
        return $this->runFunction($request, $response);
    }


    /**
     * Healthz route.
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response $response
     */
    public function healthz(Request $request, Response $response, array $args)
    {
        $response->getBody()->write("OK");
        return $response->withStatus(200);
    }


    /**
     * Run the slim framework.
     */
    public function run()
    {
        try {
            $this->app->any('/', [$this, 'root']);
            $this->app->any('/healthz', [$this, 'healthz']);
            $this->app->run();
        } catch (HttpException $httpException) {
            $response = new \Slim\Http\Response();
            $response->getBody()->write($httpException->getHttpStatusMessage());
            $this->app->respond(
                $response->withStatus(
                    $httpException->getHttpStatusCode()
                )
            );
        } catch (\Exception $e) {
            ob_end_flush();
            ob_start();
            print $e->getMessage();
            header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
        }
    }
}
