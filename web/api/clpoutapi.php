<?
//error_reporting(E_ALL);
//ini_set("display_errors", 1);
/**************************************************************************
 *	OUTBOUND REQUESTS API - Multiple types of senders
 *
 *	@author Boris, 2016
 **************************************************************************/
/* Sender types:
 * Adcombo                  - Request from salespages (name, phonenumber)   - OUTLP (type 1)  call.php
 * Cancell User             - n/a                                           - OUTCA (type 2)  call3.php
 * Upsell Call              - All data from OMG                             - OUTUP (type 3)  call2.php
 * Other - Verify OMG Call  - All data from OMG                             - OUTOT (type 4)  undefined
 * Form Fill Brake          - Unfinished form (name, phonenumber)           - OUTPC (type 5)  call.php
 * Order Fill Brake         - Unfinished Order LP form                      - OUTOC (type 6)  call.php
 * Reorder CallPanel        - Orders from reorder list                      - OUTCP (type 7)  call4.php
 * Bulk CallPanel           - Orders from bulk list                         - OUTBP (type 8)  call5.php
 * Undecided CallPanel      - Orders from undecided popup                   - OUTUC (type 9)  call6.php
 * Reorder Mail CallPanel   - Orders from mail reorder pages                - OUTRM (type 10) call7.php
 * SMS Link CallPanel       - Orders from smsLink                           - OUTSL (type 11) call8.php
 * Undecided from Presel    - Orders from Presell page                      - OUTPP (type 12) call6.php
 */

/* Statuses:
 * 0    - PENDING
 * 1    - ANSWERED
 * 2    - BUSY
 * 4    - FAKE
 * 6    - CANCELED
 * 7    - FINISHED ORDER
 * 8    - ERROR
 * 9    - POSTPONED
 * 10   - INBOUND
 * 11   - CALLING
 * 12   - FINISHED VERIFY
 * 13   - NOT CALLED
 * 14   - REMOVED
 */
// removed 3, 5

/* Errors:
 * 0    - No errors
 * 1    - Not valid API key, Not valid action, empty type
 * 2    - Not valid type
 * 3    - Some of data is missing for OUTLP request
 * 4    - No order Id found
 * 5    - FAKE
 * 6    - CANCELED
 * 7    - FINISHED
 * 8    - CALL
 * 9    - Phone is not valid
 * 10   - Phone is blacklisted
 * 11   - Number has allready order
 * 12   - Number has allready outbound
 */
