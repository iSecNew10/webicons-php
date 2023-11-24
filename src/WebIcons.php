<?php

namespace iSN10\WebIcons;

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;

class WebIcons
{
	private static string $API_ENDPOINT = 'https://icons.isn10.cloud';

	private string $apiKey;
	private string $apiSecret;

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
	}

	public function getIconUrl(
		string  $url,
		?string $formats = 'png',
		?string $size = '25..250..500',
		?string $placeholder = null,
		?bool   $skipCache = false,
	): ?string
	{
		try {
			return self::$API_ENDPOINT . '/' . $this->apiKey . '/' . base64_encode(
					Crypto::encryptWithPassword(
						json_encode([
							'url' => str_replace(['http://', 'https://'], ['', ''], $url),
							'formats' => $formats,
							'size' => $size,
							'skipCache' => $skipCache,
						]),
						$this->apiSecret
					)
				);
		} catch (EnvironmentIsBrokenException $e) {
			// @todo implement exception reporting module
		}
		return empty($placeholder) ? null : $placeholder;
	}
}
