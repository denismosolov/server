<?php
declare (strict_types = 1);
/**
 * @copyright Copyright (c) 2018 John Molakvoæ (skjnldsv) <skjnldsv@protonmail.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Accessibility\Controller;

use OCA\Accessibility\AccessibilityProvider;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCSController;
use OCP\AppFramework\OCS\OCSBadRequestException;
use OCP\IConfig;
use OCP\IRequest;
use OCP\IUserSession;

class ConfigController extends OCSController {

	/** @var string */
	protected $appName;

	/** @var string */
	protected $serverRoot;

	/** @var IConfig */
	private $config;

	/** @var IUserSession */
	private $userSession;

	/** @var AccessibilityProvider */
	private $accessibilityProvider;

	/**
	 * Config constructor.
	 *
	 * @param string $appName
	 * @param IRequest $request
	 * @param IConfig $config
	 * @param IUserSession $userSession
	 * @param AccessibilityProvider $accessibilityProvider
	 */
	public function __construct(string $appName,
								IRequest $request,
								IConfig $config,
								IUserSession $userSession,
								AccessibilityProvider $accessibilityProvider) {
		parent::__construct($appName, $request);
		$this->appName               = $appName;
		$this->config                = $config;
		$this->userSession           = $userSession;
		$this->accessibilityProvider = $accessibilityProvider;
	}

	/**
	 * @NoAdminRequired
	 *
	 * Get user accessibility config
	 *
	 * @param string $key theme or font
	 * @return DataResponse
	 */
	public function getConfig(): DataResponse {
		return new DataResponse([
			'theme' => $this->config->getUserValue($this->userSession->getUser()->getUID(), $this->appName, 'theme', false),
			'font' => $this->config->getUserValue($this->userSession->getUser()->getUID(), $this->appName, 'font', false)
		]);
	}

	/**
	 * @NoAdminRequired
	 *
	 * Set theme or font config
	 *
	 * @param string $key theme or font
	 * @return DataResponse
	 * @throws Exception
	 */
	public function setConfig(string $key, $value): DataResponse {
		if ($key === 'theme' || $key === 'font') {
			$themes = $this->accessibilityProvider->getThemes();
			$fonts  = $this->accessibilityProvider->getFonts();

			if ($value === false) {
				$this->config->deleteUserValue($this->userSession->getUser()->getUID(), $this->appName, $key);
				return new DataResponse();
			}

			$availableOptions = array_map(function($option) {
				return $option['id'];
			}, array_merge($themes, $fonts));

			if (in_array($value, $availableOptions)) {
				$this->config->setUserValue($this->userSession->getUser()->getUID(), $this->appName, $key, $value);
				return new DataResponse();
			}

			throw new OCSBadRequestException('Invalid value: ' . $value);
		}

		throw new OCSBadRequestException('Invalid key: ' . $key);
	}

}