/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { CustomerEffortScore } from '@woocommerce/customer-effort-score';

/**
 * Internal dependencies
 */
import { LOCAL_STORAGE_KEYS } from '.~/constants';
import localStorage from '.~/utils/localStorage';
import useEffectRemoveNotice from '.~/hooks/useEffectRemoveNotice';
import { recordGlaEvent } from '.~/utils/tracks';

/**
 * CES prompt snackbar open
 *
 * @event gla_ces_snackbar_open
 */
/**
 * CES prompt snackbar closed
 *
 * @event gla_ces_snackbar_closed
 */
/**
 * CES modal open
 *
 * @event gla_ces_modal_open
 */
/**
 * CES feedback recorded
 *
 * @event gla_ces_feedback
 */

/**
 * A CustomerEffortScore wrapper that uses tracks to track the selected
 * customer effort score.
 *
 * compatibility-code "WC >= 7.3"
 * TODO: Remove temporary fix for WC 7.3 in which adding `secondQuestion` is required.
 * See: https://github.com/woocommerce/google-listings-and-ads/issues/1836
 *
 * @fires gla_ces_snackbar_open whenever the CES snackbar (notice) is open
 * @fires gla_ces_snackbar_closed whenever the CES snackbar (notice) is closed
 * @fires gla_ces_modal_open whenever the CES modal is open
 * @fires gla_ces_feedback whenever the CES feedback is recorded
 *
 * @param {Object} props React component props.
 * @param {string} props.eventContext Context to be used in the CES wrapper events.
 * @param {string} props.label Text to be displayed in the CES notice and modal.
 * @param {string} props.secondLabel Only WC >= 7.3. Text to be displayed for the second question in the modal.
 * @return {JSX.Element} Rendered element.
 */
const CustomerEffortScorePrompt = ( { eventContext, label, secondLabel } ) => {
	// NOTE: Currently CES Prompts uses core/notices2 as a store key, this seems something temporal
	// and probably will be needed to change back to core/notices.
	// See: https://github.com/woocommerce/woocommerce/blob/6.6.0/packages/js/notices/src/store/index.js
	useEffectRemoveNotice( label, 'core/notices2' );

	const removeCESPromptFlagFromLocal = () => {
		localStorage.remove(
			LOCAL_STORAGE_KEYS.CAN_ONBOARDING_SETUP_CES_PROMPT_OPEN
		);
	};

	const onNoticeShown = () => {
		removeCESPromptFlagFromLocal();
		recordGlaEvent( 'gla_ces_snackbar_open', {
			context: eventContext,
		} );
	};

	const onNoticeDismissed = () => {
		recordGlaEvent( 'gla_ces_snackbar_closed', {
			context: eventContext,
		} );
	};

	const onModalShown = () => {
		recordGlaEvent( 'gla_ces_modal_open', {
			context: eventContext,
		} );
	};

	const recordScore = ( score, score2, comments ) => {
		recordGlaEvent( 'gla_ces_feedback', {
			context: eventContext,
			score,
			comments: comments || '',
		} );
	};

	return (
		<CustomerEffortScore
			label={ label }
			title={ label }
			firstQuestion={ label }
			secondQuestion={ secondLabel }
			recordScoreCallback={ recordScore }
			onNoticeShownCallback={ onNoticeShown }
			onNoticeDismissedCallback={ onNoticeDismissed }
			onModalShownCallback={ onModalShown }
			icon={
				<span
					style={ { height: 21, width: 21 } }
					role="img"
					aria-label={ __(
						'Pencil icon',
						'google-listings-and-ads'
					) }
				>
					✏️
				</span>
			}
		/>
	);
};

export default CustomerEffortScorePrompt;
