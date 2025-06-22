<?php

declare(strict_types=1);

namespace HPlus\Validate\Command;

use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Command\Annotation\Command;
use HPlus\Validate\Aspect\ValidationAspect;
use HPlus\Validate\RuleParser;
use Symfony\Component\Console\Helper\Table;

#[Command]
class ValidationStatsCommand extends HyperfCommand
{
    /**
     * 执行的命令行
     */
    protected ?string $name = 'validate:stats';

    /**
     * 命令描述
     */
    protected string $description = '查看验证器性能统计信息';

    public function handle()
    {
        $this->info('验证器性能统计信息');
        $this->line('==================');
        
        // 获取切面缓存统计
        $aspectStats = ValidationAspect::getCacheStats();
        $this->showAspectStats($aspectStats);
        
        // 获取规则解析器缓存统计
        $parserStats = RuleParser::getCacheStats();
        $this->showParserStats($parserStats);
        
        // 显示内存使用情况
        $this->showMemoryUsage();
        
        // 显示性能建议
        $this->showRecommendations($aspectStats, $parserStats);
    }
    
    private function showAspectStats(array $stats): void
    {
        $this->line("\n验证切面统计:");
        
        $table = new Table($this->output);
        $table->setHeaders(['指标', '值']);
        $table->addRows([
            ['总请求数', $stats['total']],
            ['缓存命中', $stats['hits']],
            ['缓存未命中', $stats['misses']],
            ['命中率', $stats['hit_rate']],
            ['规则缓存数', $stats['rule_cache_size']],
            ['验证器缓存数', $stats['validator_cache_size']],
        ]);
        $table->render();
    }
    
    private function showParserStats(array $stats): void
    {
        $this->line("\n规则解析器统计:");
        
        $table = new Table($this->output);
        $table->setHeaders(['缓存类型', '数量']);
        $table->addRows([
            ['规则缓存', $stats['rule_cache']],
            ['字段缓存', $stats['field_cache']],
            ['Schema缓存', $stats['schema_cache']],
        ]);
        $table->render();
    }
    
    private function showMemoryUsage(): void
    {
        $this->line("\n内存使用情况:");
        
        $table = new Table($this->output);
        $table->setHeaders(['指标', '值']);
        $table->addRows([
            ['当前内存使用', $this->formatBytes(memory_get_usage(true))],
            ['峰值内存使用', $this->formatBytes(memory_get_peak_usage(true))],
        ]);
        $table->render();
    }
    
    private function showRecommendations(array $aspectStats, array $parserStats): void
    {
        $this->line("\n性能优化建议:");
        
        $recommendations = [];
        
        // 检查缓存命中率
        if ($aspectStats['total'] > 0) {
            $hitRate = ($aspectStats['hits'] / $aspectStats['total']) * 100;
            if ($hitRate < 90) {
                $recommendations[] = "- 缓存命中率较低({$hitRate}%)，建议检查是否有动态规则生成";
            }
        }
        
        // 检查缓存大小
        if ($aspectStats['rule_cache_size'] > 1000) {
            $recommendations[] = "- 规则缓存数量较大，建议定期清理不常用的缓存";
        }
        
        // 检查内存使用
        $memoryUsage = memory_get_usage(true);
        if ($memoryUsage > 100 * 1024 * 1024) {
            $recommendations[] = "- 内存使用超过100MB，建议优化缓存策略";
        }
        
        if (empty($recommendations)) {
            $this->info("✓ 性能状态良好，无需优化");
        } else {
            foreach ($recommendations as $recommendation) {
                $this->warn($recommendation);
            }
        }
    }
    
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
} 