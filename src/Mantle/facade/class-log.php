<?php
/**
 * Log Facade class file.
 *
 * @package Mantle
 */

namespace Mantle\Facade;

/**
 * Log Facade
 *
 * @method static \Mantle\Log\Logger set_dispatcher(\Mantle\Contracts\Events\Dispatcher $dispatcher = null)
 * @method static bool addRecord(int $level, string $message, mixed[] $context = [], DateTimeImmutable $datetime = null)
 * @method static string getName()
 * @method static \self withName(string $name)
 * @method static \self pushHandler(\Monolog\Handler\HandlerInterface $handler)
 * @method static \Monolog\Handler\HandlerInterface popHandler()
 * @method static \self setHandlers(\Monolog\Handler\HandlerInterface[] $handlers)
 * @method static \Monolog\Handler\HandlerInterface[] getHandlers()
 * @method static \self pushProcessor(callable $callback)
 * @method static callable popProcessor()
 * @method static callable[] getProcessors()
 * @method static \self useMicrosecondTimestamps(bool $micro)
 * @method static \self useLoggingLoopDetection(bool $detectCycles)
 * @method static void close()
 * @method static void reset()
 * @method static array getLevels()
 * @method static string getLevelName(int $level)
 * @method static int toMonologLevel(string|int $level)
 * @method static bool isHandling(int $level)
 * @method static \self setExceptionHandler(callable|null $callback)
 * @method static callable|null getExceptionHandler()
 * @method static void log(mixed $level, string|Stringable $message, mixed[] $context = [])
 * @method static void debug(string|Stringable $message, mixed[] $context = [])
 * @method static void info(string|Stringable $message, mixed[] $context = [])
 * @method static void notice(string|Stringable $message, mixed[] $context = [])
 * @method static void warning(string|Stringable $message, mixed[] $context = [])
 * @method static void error(string|Stringable $message, mixed[] $context = [])
 * @method static void critical(string|Stringable $message, mixed[] $context = [])
 * @method static void alert(string|Stringable $message, mixed[] $context = [])
 * @method static void emergency(string|Stringable $message, mixed[] $context = [])
 * @method static \self setTimezone(\DateTimeZone $tz)
 * @method static \DateTimeZone getTimezone()
 *
 * @see \Mantle\Log\Logger
 */
class Log extends Facade {
	/**
	 * Get the registered name of the component.
	 */
	protected static function get_facade_accessor(): string {
		return 'log';
	}
}
