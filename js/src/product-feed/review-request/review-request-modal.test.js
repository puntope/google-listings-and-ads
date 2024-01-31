jest.mock( '.~/utils/tracks', () => {
	return {
		recordGlaEvent: jest.fn(),
	};
} );

jest.mock( '.~/hooks/useDispatchCoreNotices', () => ( {
	__esModule: true,
	default: jest
		.fn()
		.mockName( 'useDispatchCoreNotices' )
		.mockImplementation( () => {
			return {
				createNotice: jest.fn(),
			};
		} ),
} ) );

jest.mock( '.~/data', () => ( {
	__esModule: true,
	useAppDispatch: jest.fn( () => {
		return {
			sendMCReviewRequest: jest.fn( () => Promise.resolve() ),
		};
	} ),
} ) );

/**
 * External dependencies
 */
import { act, fireEvent, render } from '@testing-library/react';

/**
 * Internal dependencies
 */
import ReviewRequestModal from '.~/product-feed/review-request/review-request-modal';
import { recordGlaEvent } from '.~/utils/tracks';

const issues = [
	{ code: '#1', issue: '#1' },
	{ code: '#2', issue: '#2' },
	{ code: '#3', issue: '#3' },
	{ code: '#4', issue: '#4' },
	{ code: '#5', issue: '#5' },
	{ code: '#6', issue: '#6' },
];

describe( 'Request Review Modal', () => {
	it( 'Renders when is active and there are issues', () => {
		const { queryByRole } = render(
			<ReviewRequestModal issues={ issues } isActive={ true } />
		);

		expect( queryByRole( 'dialog' ) ).toBeTruthy();
	} );

	it( "Doesn't render if its not active", () => {
		const { queryByRole } = render(
			<ReviewRequestModal issues={ issues } isActive={ false } />
		);

		expect( queryByRole( 'dialog' ) ).toBeFalsy();
	} );

	it( 'Shows maximum 5 issues and can expand the list of issues', () => {
		const { queryByText } = render(
			<ReviewRequestModal issues={ issues } isActive={ true } />
		);

		expect( queryByText( '#1' ) ).toBeTruthy();
		expect( queryByText( '#2' ) ).toBeTruthy();
		expect( queryByText( '#3' ) ).toBeTruthy();
		expect( queryByText( '#4' ) ).toBeTruthy();
		expect( queryByText( '#5' ) ).toBeTruthy();
		expect( queryByText( '#6' ) ).toBeFalsy();

		let button = queryByText( '+ 1 more issue(s)' );
		expect( queryByText( 'Show less' ) ).toBeFalsy();
		expect( button ).toBeTruthy();
		fireEvent.click( button );
		expect( recordGlaEvent ).toHaveBeenCalledWith(
			'gla_request_review_issue_list_toggle_click',
			{
				action: 'expand',
			}
		);
		expect( queryByText( '#6' ) ).toBeTruthy();
		button = queryByText( 'Show less' );
		expect( button ).toBeTruthy();
		fireEvent.click( button );
		expect( recordGlaEvent ).toHaveBeenCalledWith(
			'gla_request_review_issue_list_toggle_click',
			{
				action: 'collapse',
			}
		);
		expect( queryByText( '#6' ) ).toBeFalsy();
		expect( queryByText( 'Show less' ) ).toBeFalsy();
	} );

	it( 'Cancel button closes the modal', () => {
		const onClose = jest.fn().mockName( 'onClose' );
		const { queryByText } = render(
			<ReviewRequestModal
				issues={ issues }
				isActive={ true }
				onClose={ onClose }
			/>
		);

		const button = queryByText( 'Cancel' );
		expect( button ).toBeTruthy();
		fireEvent.click( button );
		expect( onClose ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'Request review button is active after checking the checkbox and it calls onSendRequest on click', async () => {
		jest.clearAllMocks();

		const { queryByRole } = render(
			<ReviewRequestModal issues={ issues } isActive={ true } />
		);

		const button = queryByRole( 'button', {
			name: 'Request account review',
		} );

		const checkbox = queryByRole( 'checkbox' );
		expect( button ).toBeTruthy();
		fireEvent.click( button );
		expect( recordGlaEvent ).not.toHaveBeenCalled();
		expect( checkbox ).toBeTruthy();

		expect( checkbox.checked ).toEqual( false );
		fireEvent.click( checkbox );
		expect( checkbox.checked ).toEqual( true );
		expect( recordGlaEvent ).toHaveBeenNthCalledWith(
			1,
			'gla_request_review_issues_solved_checkbox_click',
			{ action: 'check' }
		);
		fireEvent.click( button );

		expect( recordGlaEvent ).toHaveBeenNthCalledWith(
			2,
			'gla_request_review'
		);

		await act( async () => {
			// necessary to wait for the millisecond to perform the promise resolve in sendMCReviewRequest
			await Promise.resolve();
		} );

		expect( recordGlaEvent ).toHaveBeenNthCalledWith(
			3,
			'gla_request_review_success'
		);
	} );
} );
