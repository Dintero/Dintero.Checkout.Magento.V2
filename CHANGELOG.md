# Changelog

v1.8.16
- added customer types option for express checkout in admin config

v1.8.15
- fixed issue with clearing coupon code from quote on embedded express checkout

v1.8.14
- fix shipping options auto-refresh for embedded express checkout
- fixed namespace for \Magento\Framework\DB\TransactionFactory on \Dintero\Checkout\Model\Dintero class

v1.8.13
- added logic to send items data during refund call

v1.8.12
- fixed order invoice totals when a pending invoice is created after authorization

v1.8.11
- fixed issue with Dintero session amount when shipping method changed

v1.8.10
- fix the 'Invalid state change requested' error.

v1.8.9
- admin order view transaction status logic updated
- admin order view transaction link fixed

v1.8.8
- fixed fraud check flag
- fixed language per store option

v1.8.7
- Fixed broken admin order view if no transaction linked to order

v1.8.4
- corrected use of urlencode with PHP 8.1

v1.8.4
- checkout agreements support added.

v1.8.3
- fixed Payment Action in admin config not applied when Authorize and Capture selected.
- Magento 2.4.7 compatibility added
- added Dintero transaction status to admin order view
- added link to Dintero transaction on admin order view 

v1.8.2
- added embedded popout functionality
- added embedded express functionality
- added embedded express with popout functionality
- implemented coupon codes support for express checkout
- logged in customer address pre-fill
- ship-to-different address support added

v1.8.1
- callback error handling implemented

v1.8.0
- added line_id generation logic options

v1.7.15
- Fixed item calculation amount

v1.7.14
- Added option to allow selection of 'unspecified' delivery method

v1.7.13
- Session update logic when coupon code changes re-worked.

v1.7.12
- fixed logic to update Dintero session when coupon code is applied/cancelled.

v1.7.11
- Fixed duplicate embedded checkout iframe

v1.7.10
- Fixed Authorization transaction handling and Fetch info
- Fixed Order creation error
- Fixed authorization transaction handling

v1.7.9
- Added Authorized transaction handling for callbacks
- Fixed Fetch transaction info functionality

v1.7.8
- workaround for shipping callback added.

v1.7.7
- Fixed javascript error during Dintero checkout initialization

v1.7.6
- Fix capture logic
- Fix compatibility with Magento 2.4.6

v1.7.5
- Added option allowing to configure which delivery method is pickup

v1.7.4
- Fixed embedded checkout session initialization when embedded checkout is disabled

v1.7.3
- fixed error if there was no authorization transaction

v1.7.2
- fixed issue happening with authorize & capture transactions after redirect to success page

v1.7.1
- order confirmation email is sent when creating order from transaction
 
v1.7.0
- version updated
- syntax error fixed

v1.6.21
- Fixed issue with wrong shipping tax amount and percent values during express checkout

v1.6.20
- Added observer to set invoice as sales document during capture

v1.6.19
- Dintero Payment Method column added to sales order grid

v1.6.18
- Fixed issue with duplicate sessions

v1.6.17
- Error caused by wrong email id fixed.

v1.6.16
- Throw exception for embedded checkout callback

v1.6.15
- Added option to auto-create invoice for authorized transaction 
- default payment action changed to "authorize"
- store hardcode removed from callback urls

- v1.6.14
- Added option to auto-create invoice for authorized transaction 
- default payment action changed to "authorize"
- store hardcode removed from callback urls

v1.6.13
- Http Client Timeout increased

v1.6.12
- added payment product info to admin
- fixed dependency of Dintero\Checkout\Model\Api\Client::prepareData

v1.6.11
- Dintero On Hold order status added
- new option added admin configuration allowing to select order status for ON_HOLD Dintero transaction

v1.6.10
- Compatibility with Magento 2.3 fixed.

v1.6.9
- [Object] error fixed on checkout

v1.6.8
- Order Processing Log Extended

v1.6.7
- added check for authorization transaction on the return url to avoid cancelling orders on the second request
- vat number and organization name is sent during session initialization to support b2b for redirect checkout

v1.6.6
- Session cancellation added on return url if payment fails.
- On Hold transactions comment showing wrong amount fixed
- Implemented session expiration in 4 hours after initialization.

v1.6.5
- Improved redirect callback handling
- Duplicate emails and comments fixed

v1.6.4
- Fixing request to get session info
- Totals calculation fixed during invoice creation when captured is triggerred.

v1.6.2
- Transactions from Dintero that arrive with status ON_HOLD will get the status Transaction pending in Magento. When the transaction is later approved or denied, the order will be updated in Magento.

v1.6.1
- Scoping fixed

v1.6.0
- Session retrieval fix

v1.5.9.0
- PHP 7.4 Compatibility: Misplaced constructor argument

v1.5.8
- Configuration scope fixed
- Phone number format fixed

v1.5.7
- Add phone_number to billing address and strip it of non-valid characters

v1.5.6
- shipping_address would in some cases be put on the wrong part of the payload sent to server

v1.5.5
- Express checkout buttons were still visible even module was disabled

v1.5.4
- Tax re-recalculation implemented for express checkout
- Product page express checkout button width fixed

v1.5.3
- Versioning fixed

v1.5.2
- Composer Compatibility fixed

v1.5.1
- Express and Embedded checkout added

