<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class JSOutputController extends Controller
{

    /**
     * get a unique id in javascript
     * ShopifyAnalytics.lib.user().traits()
     * get a user id in javascript
     * window.ShopifyAnalytics.meta.page.customerId
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function javascript() {
        return view('javascript');
    }
}
