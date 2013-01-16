<?php

/**
 * Licensed to the Apache Software Foundation (ASF) under one or more
 * contributor license agreements. See the NOTICE file distributed with
 * this work for additional information regarding copyright ownership.
 * The ASF licenses this file to You under the Apache License, Version 2.0
 * (the "License"); you may not use this file except in compliance with
 * the License. You may obtain a copy of the License at
 *
 * 	   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

/**
 * LoggerAppenderLoggly sends log events to Loggly.com
 *
 * ## Configurable parameters: ##
 * 
 * - **logglyApiUrl** - The Loggly API url for your loggly account - see http://loggly.com/support/advanced//api-event-submission/.
 * - **timeout** - Connection timeout in seconds (optional, defaults to 
 *     'default_socket_timeout' from php.ini)
 * 
 * The events will by default be sent in blocking mode.
 * 
 * @version 0.01
 * @package log4php
 * @subpackage appenders
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 * @link http://logging.apache.org/log4php/docs/appenders/socket.html Appender documentation
 */
class LoggerAppenderLoggly extends LoggerAppender {

	/**
	 * Target host.
	 * @see http://php.net/manual/en/function.fsockopen.php 
	 */
	protected $logglyApiUrl;

	/** Connection timeout in ms. */
	protected $timeout;
	
	/** The log message - built up by multiple calls to append. */
	protected $messages = array();

	// ******************************************
	// *** Appender methods                   ***
	// ******************************************

	/** Override the default layout to use serialized. */
	public function getDefaultLayout() {
		return new LoggerLayoutSerialized();
	}

	public function activateOptions() {
		if (empty($this->logglyApiUrl)) {
			$this->warn("Required parameter [logglyApiUrl] not set. Closing appender.");
			$this->closed = true;
			return;
		}

		if (empty($this->timeout)) {
			$this->timeout = ini_get("default_socket_timeout");
		}

		$this->closed = false;
	}

	public function close() {
		if($this->closed != true) {
			foreach($this->messages as $message) {
				$json_data = json_encode($message);

				$ch = curl_init($this->logglyApiUrl);
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
				curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_HTTPHEADER, array(
						'Content-Type: application/json',
						'Content-Length: ' . strlen($json_data))
				);
				$result = curl_exec($ch);
			}
			
			$this->closed = true;
		}
	}
	
	public function append(LoggerLoggingEvent $event) {
			$this->messages[] = array(
					'timestamp' => $event->getTimeStamp(),
					'level' => $event->getLevel(),
					'logger' => $event->getLoggerName(),
					'message' => $event->getMessage()
			);
	}

	// ******************************************
	// *** Accessor methods                   ***
	// ******************************************

	/** Sets the target host. */
	public function setlogglyApiUrl($url) {
		$this->setString('logglyApiUrl', $url);
	}

	/** Sets the timeout. */
	public function setTimeout($timeout) {
		$this->setPositiveInteger('timeout', $timeout);
	}

	/** Returns the target host. */
	public function getlogglyApiUrl() {
		return $this->getString('logglyApiUrl');
	}

	/** Returns the timeout */
	public function getTimeout() {
		return $this->timeout;
	}

}
