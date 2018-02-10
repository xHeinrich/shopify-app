<?php

namespace App\Http\Controllers;

use App\Http\Requests\ShopifyCallbackRequest;
use Oseintow\Shopify\Shopify;
use Illuminate\Http\Request;
use App\Shop;

class AuthenticationController extends Controller
{

    /**
     *
     * @var array
     */
    //https://help.shopify.com/api/getting-started/authentication/oauth#scopes
    public $scope = [
            "read_products", "write_products", "read_script_tags", "write_script_tags"
        ];

    /**
     * @var Shopify
     *
     */
    protected $shopify;

    /**
     * AuthenticationController constructor.
     * @param Shopify $shopify
     */
    public function __construct(Shopify $shopify) {
        $this->shopify = $shopify;
    }

    /**
     * Request application installation from a shop,
     * @return \Illuminate\Http\RedirectResponse
     */
    public function install() {
        $shopUrl = request('shop');
        $scope = $this->scope;
        $redirectUrl = route('callback');
        $this->shopify->setShopUrl($shopUrl);
        return redirect()->to($this->shopify->getAuthorizeUrl($scope,$redirectUrl));
    }

    /**
     * Authorize shop to be installed
     * @param ShopifyCallbackRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function callback(ShopifyCallbackRequest $request) {

        $shop = null;

        //get the shop url
        $shopUrl = request('shop');

        //get a long lived access token
        $accessToken = "";

        try {
            $accessToken = $this->shopify->setShopUrl($shopUrl)->getAccessToken(request('code'));
        }catch(\Exception $ex) {
            abort(403);
        }
        //if the access token is bad, abort out of the request
        if(!$accessToken) {
            abort(403);
        }

        //if the shop is already in the database, update the access token
        if($shop = Shop::where('name', $shopUrl)->first()) {
            $shop->update([
                'name' => $shopUrl,
                'token' => $accessToken
            ]);
            //show success message
        } else {
            //if not in the database, add it
            $shop = Shop::create([
                'name' => $shopUrl,
                'token' => $accessToken
            ]);

            // create a new user for this shop
            $shop->user()->create([
                'name' => collect(explode('.', $shop->name))->first(),
                'password' => bcrypt($shop->token),
                'email' => 'admin@' . $shop->name
            ]);
            //show success message
        }

        if($shop) {
            $this->addScript($shop);
            $shop->login();
            return redirect()->route('home');
        }
        //should never get to this point
        abort(403);
        \Log::error('something broke');
    }

    /**
     * Verify the shop on hmac request
     */
    public function login() {
        $queryString = request()->getQueryString();
        if(!count($queryString)) {
            return redirect()->route('home');
        }
        if($this->shopify->verifyRequest($queryString)){
            $shop = Shop::where('name', request('shop'))->first();
            $shop->login();
            return redirect()->route('home');
        }else{
            abort(403);
        }
    }

    public function products() {
        if($shop = auth()->user()->shop()->first()) {
            $products = $this->shopify->setShopUrl($shop->name)
                ->setAccessToken($shop->token)
                ->get('admin/products.json');
            dd($products);
        }
    }

    /**
     * Add a script to the shopify store
     * @param $shop
     */
    public function addScript($shop) {
        $this->shopify = $this->shopify->setShopUrl($shop->name)
            ->setAccessToken($shop->token);
        $src = $this->shopify->post('admin/script_tags.json',
            [
                'script_tag' =>
                [
                    'event' => 'onload',
                    'src' => route('javascript')
                ]
            ]);
    }
}
