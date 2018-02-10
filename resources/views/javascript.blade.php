var shop = "{{ request('shop') }}";
var customerId = window.ShopifyAnalytics.meta.page.customerId;
console.log(shop);
if(typeof customerId === 'undefined') {
    console.log('user is not logged in');
} else {
console.log(customerId);
}
