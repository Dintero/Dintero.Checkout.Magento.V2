define(
    [
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/action/place-order',
        'Dintero_Hp/js/action/set-payment-method',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Checkout/js/model/quote',
        'Magento_Customer/js/customer-data',
        'ko',
        'Magento_Checkout/js/model/full-screen-loader'
    ],
    function ($, Component, placeOrderAction, setPaymentMethodAction, additionalValidators, quote, customerData, ko, fullScreenLoader) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'Dintero_Hp/payment/dintero'
            },
            redirectAfterPlaceOrder: false,
            isVisible: ko.observable(true),
            showButton: ko.observable(true),
            getLogoUrl: function() {
                return 'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiPz4KPHN2ZyB3aWR0aD0iMTU2cHgiIGhlaWdodD0iNDZweCIgdmlld0JveD0iMCAwIDE1NiA0NiIgdmVyc2lvbj0iMS4xIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHhtbG5zOnhsaW5rPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5L3hsaW5rIj4KICAgIDwhLS0gR2VuZXJhdG9yOiBTa2V0Y2ggNTIuNCAoNjczNzgpIC0gaHR0cDovL3d3dy5ib2hlbWlhbmNvZGluZy5jb20vc2tldGNoIC0tPgogICAgPHRpdGxlPkFydGJvYXJkIENvcHkgNTwvdGl0bGU+CiAgICA8ZGVzYz5DcmVhdGVkIHdpdGggU2tldGNoLjwvZGVzYz4KICAgIDxkZWZzPgogICAgICAgIDxwb2x5Z29uIGlkPSJwYXRoLTEiIHBvaW50cz0iNTIyIDQyMy40MzMgNjc3Ljk1MSA0MjMuNDMzIDY3Ny45NTEgMzc3IDUyMiAzNzciPjwvcG9seWdvbj4KICAgIDwvZGVmcz4KICAgIDxnIGlkPSJQYWdlLTEiIHN0cm9rZT0ibm9uZSIgc3Ryb2tlLXdpZHRoPSIxIiBmaWxsPSJub25lIiBmaWxsLXJ1bGU9ImV2ZW5vZGQiPgogICAgICAgIDxnIGlkPSJBcnRib2FyZC1Db3B5LTUiIHRyYW5zZm9ybT0idHJhbnNsYXRlKC01MjIuMDAwMDAwLCAtMzc3LjAwMDAwMCkiPgogICAgICAgICAgICA8cGF0aCBkPSJNNTI4LjYyNCw0MDYuODg5IEw1MzMuOTAzLDQwNi44ODkgQzUzNi44OCw0MDYuODg5IDUzOS4zMjcsNDA1Ljk2MSA1NDEuMjQ4LDQwNC4xMDYgQzU0My4xNjgsNDAyLjI1IDU0NC4xMjgsMzk5LjU2MSA1NDQuMTI4LDM5Ni4wNDEgQzU0NC4xMjgsMzkyLjUyMiA1NDMuMTY4LDM4OS44MjUgNTQxLjI0OCwzODcuOTUzIEM1MzkuMzI3LDM4Ni4wOCA1MzYuODk1LDM4NS4xNDUgNTMzLjk1MSwzODUuMTQ1IEw1MjguNjI0LDM4NS4xNDUgTDUyOC42MjQsNDA2Ljg4OSBaIE01MzQuMTQ0LDQxMy4wMzQgTDUyMiw0MTMuMDM0IEw1MjIsMzc5IEw1MzQuMTkxLDM3OSBDNTM5LjE1MSwzNzkgNTQzLjE5MSwzODAuNTIyIDU0Ni4zMTEsMzgzLjU2MSBDNTQ5LjQzMiwzODYuNjAyIDU1MC45OTEsMzkwLjc2MiA1NTAuOTkxLDM5Ni4wNDEgQzU1MC45OTEsNDAxLjI4OSA1NDkuNDIzLDQwNS40MzQgNTQ2LjI4Nyw0MDguNDczIEM1NDMuMTUxLDQxMS41MTQgNTM5LjEwMyw0MTMuMDM0IDUzNC4xNDQsNDEzLjAzNCBaIiBpZD0iRmlsbC0xIiBmaWxsPSIjZmZmZmZmIj48L3BhdGg+CiAgICAgICAgICAgIDxtYXNrIGlkPSJtYXNrLTIiIGZpbGw9IndoaXRlIj4KICAgICAgICAgICAgICAgIDx1c2UgeGxpbms6aHJlZj0iI3BhdGgtMSI+PC91c2U+CiAgICAgICAgICAgIDwvbWFzaz4KICAgICAgICAgICAgPGcgaWQ9IkNsaXAtNCI+PC9nPgogICAgICAgICAgICA8cGF0aCBkPSJNNTU1Ljc2OCw0MTIuMzI4IEw1NjIuMTUyLDQxMi4zMjggTDU2Mi4xNTIsMzg4LjcxMSBMNTU1Ljc2OCwzODguNzExIEw1NTUuNzY4LDQxMi4zMjggWiBNNTU1LDM4MC45ODQgQzU1NSwzNzkuODk2IDU1NS4zODQsMzc4Ljk2MSA1NTYuMTUyLDM3OC4xNzYgQzU1Ni45MiwzNzcuMzkyIDU1Ny44NDgsMzc3IDU1OC45MzYsMzc3IEM1NjAuMDI0LDM3NyA1NjAuOTUxLDM3Ny4zODUgNTYxLjcyLDM3OC4xNTIgQzU2Mi40ODgsMzc4LjkyIDU2Mi44NzIsMzc5Ljg2MyA1NjIuODcyLDM4MC45ODQgQzU2Mi44NzIsMzgyLjAzOSA1NjIuNDg4LDM4Mi45NTEgNTYxLjcyLDM4My43MjEgQzU2MC45NTEsMzg0LjQ4OCA1NjAuMDI0LDM4NC44NzEgNTU4LjkzNiwzODQuODcxIEM1NTcuODQ4LDM4NC44NzEgNTU2LjkyLDM4NC40ODggNTU2LjE1MiwzODMuNzIxIEM1NTUuMzg0LDM4Mi45NTEgNTU1LDM4Mi4wMzkgNTU1LDM4MC45ODQgWiIgaWQ9IkZpbGwtMyIgZmlsbD0iI2ZmZmZmZiIgbWFzaz0idXJsKCNtYXNrLTIpIj48L3BhdGg+CiAgICAgICAgICAgIDxwYXRoIGQ9Ik01NzQuMzg0LDM5OC42NTYgTDU3NC4zODQsNDEyLjI0IEw1NjgsNDEyLjI0IEw1NjgsMzg4LjYyMyBMNTc0LjE5MiwzODguNjIzIEw1NzQuMTkyLDM5MS41NTIgQzU3NC44NjMsMzkwLjQgNTc1LjgyMywzODkuNTE5IDU3Ny4wNzIsMzg4LjkxMiBDNTc4LjMyLDM4OC4zMDQgNTc5LjYzMSwzODggNTgxLjAwOCwzODggQzU4My43OTIsMzg4IDU4NS45MTEsMzg4Ljg3MyA1ODcuMzY3LDM5MC42MTUgQzU4OC44MjMsMzkyLjM2MSA1ODkuNTUyLDM5NC42MDcgNTg5LjU1MiwzOTcuMzU5IEw1ODkuNTUyLDQxMi4yNCBMNTgzLjE2OCw0MTIuMjQgTDU4My4xNjgsMzk4LjQ2NSBDNTgzLjE2OCwzOTcuMDU2IDU4Mi44MDgsMzk1LjkyIDU4Mi4wODgsMzk1LjA1NiBDNTgxLjM2NywzOTQuMTkxIDU4MC4yNzIsMzkzLjc2IDU3OC44LDM5My43NiBDNTc3LjQ1NSwzOTMuNzYgNTc2LjM4MywzOTQuMjI0IDU3NS41ODQsMzk1LjE1MiBDNTc0Ljc4MywzOTYuMDggNTc0LjM4NCwzOTcuMjQ4IDU3NC4zODQsMzk4LjY1NiIgaWQ9IkZpbGwtNSIgZmlsbD0iI2ZmZmZmZiIgbWFzaz0idXJsKCNtYXNrLTIpIj48L3BhdGg+CiAgICAgICAgICAgIDxwYXRoIGQ9Ik02MDIuNjA4LDM4MiBMNjAyLjYwOCwzODkuMDU1IEw2MDcuMzYsMzg5LjA1NSBMNjA3LjM2LDM5NC43MTkgTDYwMi42MDgsMzk0LjcxOSBMNjAyLjYwOCw0MDQuNjA3IEM2MDIuNjA4LDQwNS42IDYwMi44MzIsNDA2LjMwNSA2MDMuMjgsNDA2LjcxOSBDNjAzLjcyOCw0MDcuMTM3IDYwNC40MzIsNDA3LjM0NCA2MDUuMzkyLDQwNy4zNDQgQzYwNi4yNTYsNDA3LjM0NCA2MDYuOTEyLDQwNy4yNzkgNjA3LjM2LDQwNy4xNTIgTDYwNy4zNiw0MTIuNDMyIEM2MDYuNDMxLDQxMi44MTYgNjA1LjIzMSw0MTMuMDA4IDYwMy43Niw0MTMuMDA4IEM2MDEuNDU2LDQxMy4wMDggNTk5LjYzMiw0MTIuMzU5IDU5OC4yODgsNDExLjA2NCBDNTk2Ljk0NCw0MDkuNzY4IDU5Ni4yNzIsNDA3Ljk4NCA1OTYuMjcyLDQwNS43MTEgTDU5Ni4yNzIsMzk0LjcxOSBMNTkyLDM5NC43MTkgTDU5MiwzODkuMDU1IEw1OTMuMiwzODkuMDU1IEM1OTQuMzgzLDM4OS4wNTUgNTk1LjI4OCwzODguNzEzIDU5NS45MTIsMzg4LjAyMyBDNTk2LjUzNiwzODcuMzM2IDU5Ni44NDgsMzg2LjQzMiA1OTYuODQ4LDM4NS4zMTMgTDU5Ni44NDgsMzgyIEw2MDIuNjA4LDM4MiBaIiBpZD0iRmlsbC02IiBmaWxsPSIjZmZmZmZmIiBtYXNrPSJ1cmwoI21hc2stMikiPjwvcGF0aD4KICAgICAgICAgICAgPHBhdGggZD0iTTYxNi40MzMsMzk3Ljg0IEw2MjYuOTkzLDM5Ny44NCBDNjI2LjkyOCwzOTYuNTI4IDYyNi40NTYsMzk1LjQyNCA2MjUuNTc3LDM5NC41MjcgQzYyNC42OTcsMzkzLjYzMyA2MjMuNDA5LDM5My4xODQgNjIxLjcxMywzOTMuMTg0IEM2MjAuMTc3LDM5My4xODQgNjE4LjkyOSwzOTMuNjY0IDYxNy45NjksMzk0LjYyMyBDNjE3LjAwOSwzOTUuNTg0IDYxNi40OTYsMzk2LjY1NiA2MTYuNDMzLDM5Ny44NCBNNjI3LjYxNyw0MDMuOTg1IEw2MzIuOTQ1LDQwNS41NjkgQzYzMi4zMDQsNDA3Ljc0NCA2MzEuMDQ5LDQwOS41MzUgNjI5LjE3Nyw0MTAuOTQ0IEM2MjcuMzA1LDQxMi4zNTIgNjI0Ljk3Nyw0MTMuMDU3IDYyMi4xOTMsNDEzLjA1NyBDNjE4LjgsNDEzLjA1NyA2MTUuOTIsNDExLjkxMiA2MTMuNTUzLDQwOS42MjMgQzYxMS4xODQsNDA3LjMzNiA2MTAsNDA0LjI3MiA2MTAsNDAwLjQzMiBDNjEwLDM5Ni43ODMgNjExLjE1MywzOTMuODAxIDYxMy40NTYsMzkxLjQ4MSBDNjE1Ljc2MSwzODkuMTYgNjE4LjQ4LDM4OCA2MjEuNjE3LDM4OCBDNjI1LjI2NSwzODggNjI4LjEyMSwzODkuMDg4IDYzMC4xODUsMzkxLjI2NCBDNjMyLjI0OCwzOTMuNDQgNjMzLjI4MSwzOTYuNDMyIDYzMy4yODEsNDAwLjI0IEM2MzMuMjgxLDQwMC40OTYgNjMzLjI3Myw0MDAuNzg1IDYzMy4yNTcsNDAxLjEwNCBDNjMzLjI0MSw0MDEuNDI0IDYzMy4yMzMsNDAxLjY4IDYzMy4yMzMsNDAxLjg3MSBMNjMzLjE4NSw0MDIuMjA3IEw2MTYuMjg4LDQwMi4yMDcgQzYxNi4zNTMsNDAzLjc0NCA2MTYuOTYsNDA1LjAyNCA2MTguMTEzLDQwNi4wNDcgQzYxOS4yNjUsNDA3LjA3MiA2MjAuNjQsNDA3LjU4NCA2MjIuMjQxLDQwNy41ODQgQzYyNC45Niw0MDcuNTg0IDYyNi43NTIsNDA2LjM4MyA2MjcuNjE3LDQwMy45ODUiIGlkPSJGaWxsLTciIGZpbGw9IiNmZmZmZmYiIG1hc2s9InVybCgjbWFzay0yKSI+PC9wYXRoPgogICAgICAgICAgICA8cGF0aCBkPSJNNjUxLjQsMzg5LjA5NiBMNjUxLjQsMzk1LjUzIEM2NTAuNzU5LDM5NS40MDEgNjUwLjEyLDM5NS4zMzYgNjQ5LjQ4LDM5NS4zMzYgQzY0Ny42NTYsMzk1LjMzNiA2NDYuMTgzLDM5NS44NTggNjQ1LjA2NCwzOTYuODk3IEM2NDMuOTQzLDM5Ny45MzggNjQzLjM4MywzOTkuNjQxIDY0My4zODMsNDAyLjAwOCBMNjQzLjM4Myw0MTIuODA5IEw2MzcsNDEyLjgwOSBMNjM3LDM4OS4xOTIgTDY0My4xOTIsMzg5LjE5MiBMNjQzLjE5MiwzOTIuNjk4IEM2NDQuMzQzLDM5MC4yMzMgNjQ2LjU4NCwzODkgNjQ5LjkxMiwzODkgQzY1MC4yNjMsMzg5IDY1MC43NTksMzg5LjAzNCA2NTEuNCwzODkuMDk2IiBpZD0iRmlsbC04IiBmaWxsPSIjZmZmZmZmIiBtYXNrPSJ1cmwoI21hc2stMikiPjwvcGF0aD4KICAgICAgICAgICAgPHBhdGggZD0iTTY2MS4xMzYsNDA1LjQ3MyBDNjYyLjMwNCw0MDYuNjU3IDY2My43Miw0MDcuMjQ4IDY2NS4zODQsNDA3LjI0OCBDNjY3LjA0OCw0MDcuMjQ4IDY2OC40NjQsNDA2LjY1NyA2NjkuNjMyLDQwNS40NzMgQzY3MC44LDQwNC4yODkgNjcxLjM4NCw0MDIuNjQxIDY3MS4zODQsNDAwLjUyOCBDNjcxLjM4NCwzOTguNDE2IDY3MC44LDM5Ni43NjggNjY5LjYzMiwzOTUuNTg0IEM2NjguNDY0LDM5NC40MDEgNjY3LjA0OCwzOTMuODA5IDY2NS4zODQsMzkzLjgwOSBDNjYzLjcyLDM5My44MDkgNjYyLjMwNCwzOTQuNDAxIDY2MS4xMzYsMzk1LjU4NCBDNjU5Ljk2OCwzOTYuNzY4IDY1OS4zODQsMzk4LjQxNiA2NTkuMzg0LDQwMC41MjggQzY1OS4zODQsNDAyLjY0MSA2NTkuOTY4LDQwNC4yODkgNjYxLjEzNiw0MDUuNDczIE02NTYuNTI5LDM5MS41NTEgQzY1OC44OCwzODkuMTg0IDY2MS44MzIsMzg4IDY2NS4zODQsMzg4IEM2NjguOTM2LDM4OCA2NzEuODg4LDM4OS4xODQgNjc0LjI0LDM5MS41NTEgQzY3Ni41OTIsMzkzLjkyIDY3Ny43NjgsMzk2LjkxMiA2NzcuNzY4LDQwMC41MjggQzY3Ny43NjgsNDA0LjE0NSA2NzYuNTkyLDQwNy4xMzcgNjc0LjI0LDQwOS41MDQgQzY3MS44ODgsNDExLjg3MyA2NjguOTM2LDQxMy4wNTcgNjY1LjM4NCw0MTMuMDU3IEM2NjEuODMyLDQxMy4wNTcgNjU4Ljg4LDQxMS44NzMgNjU2LjUyOSw0MDkuNTA0IEM2NTQuMTc2LDQwNy4xMzcgNjUzLDQwNC4xNDUgNjUzLDQwMC41MjggQzY1MywzOTYuOTEyIDY1NC4xNzYsMzkzLjkyIDY1Ni41MjksMzkxLjU1MSIgaWQ9IkZpbGwtOSIgZmlsbD0iI2ZmZmZmZiIgbWFzaz0idXJsKCNtYXNrLTIpIj48L3BhdGg+CiAgICAgICAgICAgIDxwb2x5Z29uIGlkPSJGaWxsLTEwIiBmaWxsPSIjMDBFNTkwIiBtYXNrPSJ1cmwoI21hc2stMikiIHBvaW50cz0iNjU0IDQyMy4zODMgNjc3LjYxNiA0MjMuMzgzIDY3Ny42MTYgNDE3IDY1NCA0MTciPjwvcG9seWdvbj4KICAgICAgICA8L2c+CiAgICA8L2c+Cjwvc3ZnPg==';
            },
            continueToDintero: function () {
                if (additionalValidators.validate()) {
                    this.selectPaymentMethod();
                    setPaymentMethodAction(this.messageContainer).done(this.placeOrder);
                    return false;
                }
            },

            placeOrder: function () {
                customerData.invalidate(['cart']);
                $.ajax({
                    url: window.checkoutConfig.payment.dintero.placeOrderUrl,
                    type: 'post',
                    context: this,
                    dataType: 'json',
                    beforeSend: function () {
                        fullScreenLoader.startLoader();
                    },
                    success: function (response) {
                        var preparedData,
                            msg,

                            /**
                             * {Function}
                             */
                            alertActionHandler = function () {
                                // default action
                            };

                        if (response.url) {
                            $.mage.redirect(response.url);
                        } else {
                            fullScreenLoader.stopLoader(true);

                            msg = response['error'];
                            if (typeof msg === 'object') {
                                msg = msg.join('\n');
                            }

                            if (msg) {
                                alert(
                                    {
                                        content: msg,
                                        actions: {

                                            /**
                                             * {Function}
                                             */
                                            always: alertActionHandler
                                        }
                                    }
                                );
                            }
                        }
                    }
                });
            }
        });
    }
);