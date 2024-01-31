/**
 * @typedef {Object} GeneralState
 * @property {string} version The version of this extension. Null if not yet connected.
 * @property {number | null} mcId The ID of the connected Google Merchant Center account. Null if not yet connected.
 * @property {number | null} adsId The ID of the connected Google Ads account. Null if not yet connected.
 */

/**
 * @typedef {Object} SuggestedAssets
 * @property {string} business_name The name of merchant's business or brand.
 * @property {string} final_url The page URL on merchant's website that people reach when they click the ad.
 * @property {string[]} headline The headlines for the ad.
 * @property {string[]} long_headline The headlines for the larger ad.
 * @property {string[]} description The descriptive text for the ad to provide additional context or details.
 * @property {string[]} marketing_image The URLs of the landscape images.
 * @property {string[]} square_marketing_image The URLs of the square images.
 * @property {string[]} portrait_marketing_image The URLs of the portrait images.
 * @property {string[]} logo The URLs of the logo images.
 * @property {string[]} display_url_path The path part of the display URL on the ad.
 * @property {string | null} call_to_action_selection The call-to-action text on the ad to let users know what the ad will get them to do. `null` if not selected.
 */

/**
 * @typedef {Object} AssetEntity
 * @property {number} id The ID of the asset.
 * @property {string} content The content of the asset.
 */

/**
 * @typedef {Object} AssetsDictionary
 * @property {AssetEntity} [business_name] The name of merchant's business or brand.
 * @property {AssetEntity[]} [headline] The headlines for the ad.
 * @property {AssetEntity[]} [long_headline] The headlines for the larger ad.
 * @property {AssetEntity[]} [description] The descriptive text for the ad to provide additional context or details.
 * @property {AssetEntity[]} [marketing_image] The URLs of the landscape images.
 * @property {AssetEntity[]} [square_marketing_image] The URLs of the square images.
 * @property {AssetEntity[]} [portrait_marketing_image] The URLs of the portrait images.
 * @property {AssetEntity[]} [logo] The URLs of the logo images.
 * @property {AssetEntity} [call_to_action_selection] The call-to-action text on the ad to let users know what the ad will get them to do. `null` if not selected.
 */

/**
 * @typedef {Object} AssetEntityGroup
 * @property {number} id The ID of the asset group.
 * @property {AssetsDictionary} assets The asset entities of the asset group.
 * @property {string} final_url The page URL on merchant's website that people reach when they click the ad.
 * @property {string[]} display_url_path The path part of the display URL on the ad.
 */

/**
 * @typedef {Object} AssetOperations
 * @property {number | null} id The ID of the asset. Set `null` to indicate the asset creation operation.
 * @property {string | null} content The content of the asset. Set `null` to indicate the asset deletion operation.
 * @property {string} field_type The enum field type of the asset.
 */

/**
 * @typedef {Object} AssetEntityGroupUpdateBody
 * @property {string} final_url The page URL on merchant's website that people reach when they click the ad.
 * @property {string} path1 The first path of the display URL on the ad.
 * @property {string} path2 The second path of the display URL on the ad.
 * @property {AssetOperations[]} assets The creation and deletion operations for updating the asset group.
 */

// This export is required for JSDoc in other files to import the type definitions from this file.
export default {};
