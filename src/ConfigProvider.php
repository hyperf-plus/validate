<?php

declare(strict_types=1);

namespace HPlus\Validate;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => [
                // 注册核心服务
                Validate::class => Validate::class,
                RuleParser::class => RuleParser::class,
            ],
            'annotations' => [
                'scan' => [
                    'paths' => [
                        __DIR__,
                    ],
                ],
            ],
            'aspects' => [
                // 注册验证切面
                Aspect\ValidationAspect::class,
            ],
            'listeners' => [
                // 注册启动监听器
                Listener\BootValidationListener::class,
            ],
            'publish' => [],
        ];
    }
}