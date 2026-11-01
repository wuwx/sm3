# SM3

[![Latest Version on Packagist](https://img.shields.io/packagist/v/wuwx/sm3.svg?style=flat-square)](https://packagist.org/packages/wuwx/sm3)
[![run-tests](https://github.com/wuwx/sm3/actions/workflows/run-tests.yml/badge.svg)](https://github.com/wuwx/sm3/actions/workflows/run-tests.yml)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

国密 SM3 散列（GB/T 32905-2016）的纯 PHP 实现。无扩展依赖，32 位与 64 位 PHP 结果一致。

> ⚠️ **不适用于口令存储**：SM3 是通用散列函数，没有盐值和迭代，不能替代 bcrypt / argon2 做口令哈希。

## 安装

```bash
composer require wuwx/sm3
```

## 使用

```php
use Wuwx\Sm3\Sm3;

$hash = Sm3::hash('abc');
// 66c7f0f462eeedd9d1f2d46bdc10e4e24167c4875cf2f7a2297da02b8f4ba8e0
```

返回值固定为 64 位小写十六进制字符串。

## 性能

本机实测（PHP 8.5，未启用 JIT）：

| 输入 | 耗时 | 吞吐 |
|---|---|---|
| 6 B | 80 µs | — |
| 1 KB | 1.4 ms | 0.7 MB/s |
| 10 KB | 13.5 ms | 0.75 MB/s |
| 100 KB | 136 ms | 0.75 MB/s |
| 1 MB | 1.3 s | 0.8 MB/s |

纯 PHP 实现的吞吐就在 1 MB/s 量级，适合短串、单条报文的摘要；大文件不要用它，那种场景直接用 `openssl_digest($data, 'sm3')` 更合适。

## 从 ch4o5/sm3-php 迁移

- API 不同，**不是 drop-in 替换**：本包不提供全局 `sm3()` 函数，需改用 `Sm3::hash()`
- 空串结果不同：本包输出 GB/T 32905 下的标准值 `1ab21d83...`，若有空串摘要的存量数据需要重新生成

## 测试

```bash
composer test
```

## 许可证

MIT，详见 [LICENSE](LICENSE)。
