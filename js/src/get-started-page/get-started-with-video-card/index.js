/**
 * External dependencies
 */
import { FlexBlock, Card, CardBody, Tip } from '@wordpress/components';
import { createInterpolateElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { glaData } from '.~/constants';
import AppButton from '.~/components/app-button';
import Text from '.~/components/app-text';
import AppDocumentationLink from '.~/components/app-documentation-link';
import WistiaVideo from '.~/components/wistia-video';
import { getSetupMCUrl } from '.~/utils/urls';
import './index.scss';

/**
 * @fires gla_setup_mc with `{ triggered_by: 'start-onboarding-button', action: 'go-to-onboarding', context: 'get-started-with-video', target: 'set_up_free_listings', trigger: 'click' }`.
 * @fires gla_documentation_link_click with `{ context: 'get-started-with-video', linkId: 'wp-terms-of-service', href: 'https://wordpress.com/tos/' }`.
 */
const GetStartedWithVideoCard = () => {
	const disableNextStep = ! glaData.mcSupportedLanguage;

	return (
		<Card className="gla-get-started-with-video-card" isBorderless>
			<FlexBlock className="motivation-video">
				<WistiaVideo
					id="lpvgtsjwrg"
					src="https://fast.wistia.net/embed/iframe/lpvgtsjwrg?seo=false&videoFoam=true"
					title="WooCommerce-Google-Listings-Ads"
				/>
			</FlexBlock>
			<CardBody>
				<Text
					variant="caption"
					className="gla-get-started-with-video-card__caption"
				>
					{ __(
						'The official extension for WooCommerce, built in collaboration with Google',
						'google-listings-and-ads'
					) }
				</Text>
				<Text
					variant="title-medium"
					className="gla-get-started-with-video-card__title"
				>
					{ __(
						'Reach millions of shoppers with product listings on Google',
						'google-listings-and-ads'
					) }
				</Text>
				<Text
					variant="body"
					className="gla-get-started-with-video-card__description"
				>
					{ __(
						'Sync your products directly to Google, manage your product feed, and create Google Ad campaigns — all without leaving your WooCommerce dashboard.',
						'google-listings-and-ads'
					) }
				</Text>
				<AppButton
					className="gla-get-started-with-video-card__button"
					isPrimary
					disabled={ disableNextStep }
					href={ getSetupMCUrl() }
					eventName="gla_setup_mc"
					eventProps={ {
						triggered_by: 'start-onboarding-button',
						action: 'go-to-onboarding',
						context: 'get-started-with-video',
						// 'target' and 'trigger' were deprecated and can be removed after Q1 2024.
						target: 'set_up_free_listings',
						trigger: 'click',
					} }
				>
					{ __(
						'Start listing products →',
						'google-listings-and-ads'
					) }
				</AppButton>
				<Text className="gla-get-started-with-video-card__hint">
					{ __(
						'Estimated setup time: 15 min',
						'google-listings-and-ads'
					) }
				</Text>
				<Text
					className="gla-get-started-with-video-card__terms-notice"
					variant="body"
				>
					{ createInterpolateElement(
						__(
							'By clicking ‘Start listing products’, you agree to our <link>Terms of Service.</link>',
							'google-listings-and-ads'
						),
						{
							link: (
								<AppDocumentationLink
									context="get-started-with-video"
									linkId="wp-terms-of-service"
									href="https://wordpress.com/tos/"
								/>
							),
						}
					) }
				</Text>
			</CardBody>
			<Tip>
				{ __(
					'If you’re already using another extension to manage your product feed with Google, make sure to deactivate or uninstall it first to prevent duplicate product feeds.',
					'google-listings-and-ads'
				) }
			</Tip>
		</Card>
	);
};

export default GetStartedWithVideoCard;
