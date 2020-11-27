<?php

namespace App\Middleware;

use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface as HttpResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Phper666\JWTAuth\JWT;
use Phper666\JWTAuth\Util\JWTUtil;

/**
 * Http Token 授权验证中间件
 *
 * @package App\Middleware
 */
class JWTAuthMiddleware implements MiddlewareInterface
{
    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var HttpResponse
     */
    protected $response;

    /**
     * @var JWT
     */
    protected $jwt;

    public function __construct(HttpResponse $response, RequestInterface $request, JWT $jwt)
    {
        $this->response = $response;
        $this->request = $request;
        $this->jwt = $jwt;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $isValidToken = true;

        // 获取请求token
        $token = $request->getHeaderLine('Authorization') ?? $this->request->input('token', '');

        if (!empty($token)) {
            try {
                $token = JWTUtil::handleToken($token);
                if ($token !== false && $this->jwt->checkToken($token)) {
                    $isValidToken = true;
                }
            } catch (\Exception $e) {
                $isValidToken = false;
            }
        }

        if (!$isValidToken) {
            return $this->response->withStatus(401)->json([
                'code' => 401,
                'message' => 'Token authentication does not pass',
                'data' => []
            ]);
        }

        return $handler->handle($request);
    }
}