<?php

namespace iSN10\WebIcons;

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;

class WebIcons
{
	private static string $API_ENDPOINT = 'https://icons2.isn10.cloud';

	private string $apiKey;
	private string $apiSecret;

	private array $runtimeCache = [];

	/**
	 * @throws \Exception
	 */
	public static function default(): WebIcons
	{
		(empty($_ENV['ISN_WEB_ICONS_API_KEY']) || empty($_ENV['ISN_WEB_ICONS_API_SECRET'])) && throw new \Exception('iSN10 WebIcons API-Key/Secret missing');
		return new WebIcons($_ENV['ISN_WEB_ICONS_API_KEY'], $_ENV['ISN_WEB_ICONS_API_SECRET']);
	}

	public function __construct(string $apiKey, string $apiSecret)
	{
		$this->apiKey = $apiKey;
		$this->apiSecret = $apiSecret;

		if (file_exists($this->getCachePath())) {
			$this->runtimeCache = json_decode(file_get_contents($this->getCachePath()), true);
		}
	}

	public function getIconUrl(
		string  $url,
		?string $formats = 'png',
		?string $size = '25..250..500',
		?string $placeholder = null,
		?bool   $skipCache = false,
		?bool   $skipRemoteCache = false,
	): ?string
	{
		if (empty($skipCache) && !empty($cachedUrl = $this->getCachedIconUrl($url, $formats, $size))) {
			return $cachedUrl;
		}
		try {
			$iconUrl = self::$API_ENDPOINT . '/' . $this->apiKey . '/' . base64_encode(
					Crypto::encryptWithPassword(
						json_encode([
							'url' => str_replace(['http://', 'https://'], ['', ''], $url),
							'formats' => $formats,
							'size' => $size,
							'skipCache' => $skipRemoteCache,
						]),
						$this->apiSecret
					)
				);
			$this->setCachedIconUrl($this->getCacheKey(
				$url, $formats, $size
			), $iconUrl);
			return $iconUrl;
		} catch (EnvironmentIsBrokenException $e) {
			// @todo implement exception reporting module
		}
		return empty($placeholder) ? null : $placeholder;
	}

	private function getCacheKey(
		string  $url,
		?string $formats = 'png',
		?string $size = '25..250..500',
	): string
	{
		return md5(
			json_encode([
				'url' => $url,
				'format' => $formats,
				'size' => $size,
			])
		);
	}

	private function setCachedIconUrl(string $cacheKey, string $iconUrl): void
	{
		$this->runtimeCache[$cacheKey] = $iconUrl;
		file_put_contents($this->getCachePath(), json_encode($this->runtimeCache));
	}

	private function getCachedIconUrl(
		string  $url,
		?string $formats = 'png',
		?string $size = '25..250..500',
	): ?string
	{
		$cacheKey = $this->getCacheKey($url, $formats, $size);
		if (!in_array($cacheKey, array_keys($this->runtimeCache))) {
			return null;
		}
		return $this->runtimeCache[$cacheKey];
	}

	private function getCachePath(): string
	{
		return ROOT_PATH . '/var/cache/sys/webicons.cache';
	}
}
