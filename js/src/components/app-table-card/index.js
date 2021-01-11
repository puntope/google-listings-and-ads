/**
 * External dependencies
 */
import { TableCard } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import recordColumnToggleEvent from './recordColumnToggleEvent';
import { recordTableSortEvent } from '../../utils/recordEvent';
import './index.scss';

const AppTableCard = ( props ) => {
	const { trackEventReportId, ...rest } = props;
	/**
	 * Returns a function that records a track event before executing an original handler.
	 *
	 * @param {String} handlerName The name of the event handler.
	 * @param {Function} recordEvent The function to record the event.
	 *
	 * @returns {decorateHandlerWithTrackEvent~decoratedHandler} Decorated handler.
	 */
	const decorateHandlerWithTrackEvent = ( handlerName, recordEvent ) => {
		/**
		 * Records track event with specified `trackEventReportId` and any args given,
		 * then calls original handler if any.
		 */
		return function decoratedHandler( ...args ) {
			if ( trackEventReportId ) {
				recordEvent( trackEventReportId, ...args );
			}

			// Call the original handler if given.
			const handler = props.handlerName;
			if( handler ){
				handler( ...args );
			}
		}
	}

	return (
		<div className="app-table-card">
			<TableCard
				onColumnsChange={ decorateHandlerWithTrackEvent( 'onColumnsChange', recordColumnToggleEvent) }
				onSort={ decorateHandlerWithTrackEvent( 'onSort', recordTableSortEvent) }
				{ ...rest } />
		</div>
	);
};

export default AppTableCard;
