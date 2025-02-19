<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Whiteboard\Service;

use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use OCA\Whiteboard\Consts\JWTConsts;
use OCA\Whiteboard\Exception\UnauthorizedException;
use OCA\Whiteboard\Model\User;
use OCP\Files\File;
use OCP\Files\InvalidPathException;
use OCP\Files\NotFoundException;

final class JWTService {
	public function __construct(
		private ConfigService $configService
	) {
	}

	/**
	 * @throws InvalidPathException
	 * @throws NotFoundException
	 */
	public function generateJWT(User $user, File $file, bool $isFileReadOnly = true): string {
		$issuedAt = time();
		$expirationTime = $issuedAt + JWTConsts::EXPIRATION_TIME;
		$payload = [
			'userid' => $user->getUID(),
			'fileId' => $file->getId(),
			'isFileReadOnly' => $isFileReadOnly,
			'user' => [
				'id' => $user->getUID(),
				'name' => $user->getDisplayName()
			],
			'iat' => $issuedAt,
			'exp' => $expirationTime
		];

		return $this->generateJWTFromPayload($payload);
	}

	/**
	 * @throws InvalidPathException
	 * @throws NotFoundException
	 */
	public function generateJWTFromPayload(array $payload): string {
		$key = $this->configService->getJwtSecretKey();
		return JWT::encode($payload, $key, JWTConsts::JWT_ALGORITHM);
	}

	public function getUserIdFromJWT(string $jwt): string {
		try {
			$key = $this->configService->getJwtSecretKey();
			return JWT::decode($jwt, new Key($key, JWTConsts::JWT_ALGORITHM))->userid;
		} catch (Exception) {
			throw new UnauthorizedException();
		}
	}
}
