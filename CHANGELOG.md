# Changelog

v1.6.14
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
