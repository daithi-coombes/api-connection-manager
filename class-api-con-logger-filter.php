<?php
class API_Con_Logger_Filter_Request extends LoggerFilter {

	/**
	 * Always returns the integer constant {@link LoggerFilter::DENY}
	 * regardless of the {@link LoggerLoggingEvent} parameter.
	 * 
	 * @param LoggerLoggingEvent $event The {@link LoggerLoggingEvent} to filter.
	 * @return LoggerFilter::DENY Always returns {@link LoggerFilter::DENY}
	 */
	public function decide(LoggerLoggingEvent $event) {
		//if response, set appender
		if(defined('DOING_AJAX'))
			return LoggerFilter::DENY;
	}
}
class API_Con_Logger_Filter_Response extends LoggerFilter {

	/**
	 * Always returns the integer constant {@link LoggerFilter::DENY}
	 * regardless of the {@link LoggerLoggingEvent} parameter.
	 * 
	 * @param LoggerLoggingEvent $event The {@link LoggerLoggingEvent} to filter.
	 * @return LoggerFilter::DENY Always returns {@link LoggerFilter::DENY}
	 */
	public function decide(LoggerLoggingEvent $event) {
		//if response, set appender
		if(!defined('DOING_AJAX'))
			return LoggerFilter::DENY;
	}
}
