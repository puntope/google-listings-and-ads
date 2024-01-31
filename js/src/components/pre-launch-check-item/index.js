/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	Panel,
	PanelBody,
	PanelRow,
	CheckboxControl,
} from '@wordpress/components';
import { useRef } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useAdaptiveFormContext } from '.~/components/adaptive-form';
import AppButton from '.~/components/app-button';
import { recordGlaEvent } from '.~/utils/tracks';
import './index.scss';

const getPanelToggleHandler = ( id ) => ( isOpened ) => {
	recordGlaEvent( 'pre-launch-checklist', {
		id,
		action: isOpened ? 'expand' : 'collapse',
	} );
};

export default function PreLaunchCheckItem( {
	fieldName,
	firstPersonTitle,
	secondPersonTitle,
	children,
} ) {
	const { getInputProps, setValue, values } = useAdaptiveFormContext();
	const checked = values[ fieldName ];
	const initialCheckedRef = useRef( checked );

	if ( checked ) {
		return (
			<CheckboxControl
				label={
					initialCheckedRef.current
						? firstPersonTitle
						: secondPersonTitle
				}
				{ ...getInputProps( fieldName ) }
				disabled
			/>
		);
	}

	return (
		<div className="gla-pre-launch-checklist__checkbox">
			<CheckboxControl { ...getInputProps( fieldName ) } />
			<Panel>
				<PanelBody
					title={ secondPersonTitle }
					initialOpen={ false }
					onToggle={ getPanelToggleHandler( fieldName ) }
				>
					<PanelRow>
						{ children }
						<AppButton
							isPrimary
							onClick={ () => setValue( fieldName, true ) }
						>
							{ __( 'Confirm', 'google-listings-and-ads' ) }
						</AppButton>
					</PanelRow>
				</PanelBody>
			</Panel>
		</div>
	);
}
