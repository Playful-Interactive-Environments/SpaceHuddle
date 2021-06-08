<?php

use App\Factory\LoggerFactory;
use App\Handler\DefaultErrorHandler;
use App\Routing\JwtAuth;
use Cake\Database\Connection;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Selective\BasePath\BasePathMiddleware;
use Selective\Validation\Encoder\JsonEncoder;
use Selective\Validation\Middleware\ValidationExceptionMiddleware;
use Selective\Validation\Transformer\ErrorDetailsResultTransformer;
use Slim\App;
use Slim\Factory\AppFactory;
use Slim\Interfaces\RouteParserInterface;
use Slim\Middleware\ErrorMiddleware;
use Slim\Views\PhpRenderer;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputOption;

return [
    // Application settings
    "settings" => function () {
        return require __DIR__ . "/settings.php";
    },

    App::class => function (ContainerInterface $container) {
        AppFactory::setContainer($container);

        return AppFactory::create();
    },

    // HTTP factories
    ResponseFactoryInterface::class => function (ContainerInterface $container) {
        return $container->get(App::class)->getResponseFactory();
    },

    ServerRequestFactoryInterface::class => function () {
        return new Psr17Factory();
    },

    StreamFactoryInterface::class => function () {
        return new Psr17Factory();
    },

    UploadedFileFactoryInterface::class => function () {
        return new Psr17Factory();
    },

    UriFactoryInterface::class => function () {
        return new Psr17Factory();
    },

    // The Slim RouterParser
    RouteParserInterface::class => function (ContainerInterface $container) {
        return $container->get(App::class)->getRouteCollector()->getRouteParser();
    },

    // The logger factory
    LoggerFactory::class => function (ContainerInterface $container) {
        return new LoggerFactory($container->get("settings")["logger"]);
    },

    BasePathMiddleware::class => function (ContainerInterface $container) {
        $app = $container->get(App::class);

        return new BasePathMiddleware($app);
    },

    // Database connection
    Connection::class => function (ContainerInterface $container) {
        return new Connection($container->get("settings")["db"]);
    },

    PDO::class => function (ContainerInterface $container) {
        $db = $container->get(Connection::class);
        $driver = $db->getDriver();
        $driver->connect();

        return $driver->getConnection();
    },

    ValidationExceptionMiddleware::class => function (ContainerInterface $container) {
        $factory = $container->get(ResponseFactoryInterface::class);

        return new ValidationExceptionMiddleware(
            $factory,
            new ErrorDetailsResultTransformer(),
            new JsonEncoder()
        );
    },

    ErrorMiddleware::class => function (ContainerInterface $container) {
        $settings = $container->get("settings")["error"];
        $app = $container->get(App::class);

        $logger = $container->get(LoggerFactory::class)
            ->addFileHandler("error.log")
            ->createLogger();

        $errorMiddleware = new ErrorMiddleware(
            $app->getCallableResolver(),
            $app->getResponseFactory(),
            (bool)$settings["display_error_details"],
            (bool)$settings["log_errors"],
            (bool)$settings["log_error_details"],
            $logger
        );

        $errorMiddleware->setDefaultErrorHandler($container->get(DefaultErrorHandler::class));
        $errorMiddleware->setErrorHandler(PDOException::class, \App\Domain\Base\RepositoryErrorHandling::class);

        return $errorMiddleware;
    },

    Application::class => function (ContainerInterface $container) {
        $application = new Application();

        $application->getDefinition()->addOption(
            new InputOption("--env", "-e", InputOption::VALUE_REQUIRED, "The Environment name.", "development")
        );

        foreach ($container->get("settings")["commands"] as $class) {
            $application->add($container->get($class));
        }

        return $application;
    },

    PhpRenderer::class => function (ContainerInterface $container) {
        return new PhpRenderer($container->get("settings")["template"]);
    },

    JwtAuth::class => function (ContainerInterface $container) {
        $configuration = $container->get(Configuration::class);
        $jwtSettings = $container->get("settings")["jwt"];
        $issuer = (string)$jwtSettings["issuer"];
        $lifetime = (int)$jwtSettings["lifetime"];
        return new JwtAuth($configuration, $issuer, $lifetime);
    },

    Configuration::class => function (ContainerInterface $container) {
        $jwtSettings = $container->get("settings")["jwt"];
        $privateKey = (string)$jwtSettings["private_key"];
        $publicKey = (string)$jwtSettings["public_key"];
        // Asymmetric algorithms use a private key for signature creation
        // and a public key for verification
        return Configuration::forAsymmetricSigner(
            new Sha256(),
            InMemory::plainText($privateKey),
            InMemory::plainText($publicKey)
        );
    },
];
