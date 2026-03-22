<?php

if ( ! defined( 'ABSPATH' ) ) { exit; }

if (! class_exists('affiliatepress_global_options') ) {
    class affiliatepress_global_options Extends AffiliatePress_Core{

        function __construct(){

        }
        
        /**
         * All global options for AffiliatePress
         *
         * @return void
         */
        function affiliatepress_global_options(){   
            global $affiliatepress,$wpdb;
            
            $affiliatepress_site_current_language = $this->affiliatepress_get_site_current_language();
            
            if ($affiliatepress_site_current_language == 'ru' ) {
                $affiliatepress_site_current_language = 'ruRU';
            } elseif ($affiliatepress_site_current_language == 'zh-cn' ) {
                $affiliatepress_site_current_language = 'zhCN';
            } elseif ($affiliatepress_site_current_language == 'pt-br' ) {
                $affiliatepress_site_current_language = 'ptBr';
            } elseif ($affiliatepress_site_current_language == 'sv' ) {
                $affiliatepress_site_current_language = 'se';
            } else if( 'tr' == $affiliatepress_site_current_language ){
                $affiliatepress_site_current_language = 'trTR';
            } else if( 'nl-be' == $affiliatepress_site_current_language ){
                $affiliatepress_site_current_language = 'nlBE';
            }elseif ($affiliatepress_site_current_language == 'pt' ) {
                $affiliatepress_site_current_language = 'ptPT';
            }elseif ($affiliatepress_site_current_language == 'et' ) {
                $affiliatepress_site_current_language = 'et';
            }elseif ($affiliatepress_site_current_language == 'nb_NO' ) {
                $affiliatepress_site_current_language = 'no';
            }elseif ($affiliatepress_site_current_language == 'lv' ) {
                $affiliatepress_site_current_language = 'lv';
            }elseif ($affiliatepress_site_current_language == 'az' ) {
                $affiliatepress_site_current_language = 'az';
            }elseif ($affiliatepress_site_current_language == 'fi' ) {
                $affiliatepress_site_current_language = 'fi';
            }elseif ($affiliatepress_site_current_language == 'gl_ES' ) {
                $affiliatepress_site_current_language = 'gl';
            }elseif ($affiliatepress_site_current_language == 'he_IL' ) {
                $affiliatepress_site_current_language = 'he';
            }            

            $affiliatepress_global_data = array(
                'name'                         => 'AffiliatePress',
                'debug'                        => true,
                'locale'                       => $affiliatepress_site_current_language,
                'start_of_week'                => get_option('start_of_week'),
                'country_lists'                => '[{"code":"ad","name":"Andorra"},{"code":"ae","name":"United Arab Emirates"},{"code":"af","name":"Afghanistan"},{"code":"ag","name":"Antigua & Barbuda"},{"code":"ai","name":"Anguilla"},{"code":"al","name":"Albania"},{"code":"am","name":"Armenia"},{"code":"ao","name":"Angola"},{"code":"aq","name":"Antarctica"},{"code":"ar","name":"Argentina"},{"code":"as","name":"American Samoa"},{"code":"at","name":"Austria"},{"code":"au","name":"Australia"},{"code":"aw","name":"Aruba"},{"code":"ax","name":"Aland Islands"},{"code":"az","name":"Azerbaijan"},{"code":"ba","name":"Bosnia & Herzegovina"},{"code":"bb","name":"Barbados"},{"code":"bd","name":"Bangladesh"},{"code":"be","name":"Belgium"},{"code":"bf","name":"Burkina Faso"},{"code":"bg","name":"Bulgaria"},{"code":"bh","name":"Bahrain"},{"code":"bi","name":"Burundi"},{"code":"bj","name":"Benin"},{"code":"bm","name":"Bermuda"},{"code":"bn","name":"Brunei"},{"code":"bo","name":"Bolivia"},{"code":"br","name":"Brazil"},{"code":"bs","name":"Bahamas"},{"code":"bt","name":"Bhutan"},{"code":"bv","name":"Bouvet Island"},{"code":"bw","name":"Botswana"},{"code":"by","name":"Belarus"},{"code":"bz","name":"Belize"},{"code":"ca","name":"Canada"},{"code":"cc","name":"Cocos (Keeling) Islands"},{"code":"cd","name":"Congo - Kinshasa"},{"code":"cf","name":"Central African Republic"},{"code":"cg","name":"Congo - Brazzaville"},{"code":"ch","name":"Switzerland"},{"code":"ci","name":"Cote D\'Ivoire (Ivory Coast)"},{"code":"ck","name":"Cook Islands"},{"code":"cl","name":"Chile"},{"code":"cm","name":"Cameroon"},{"code":"cn","name":"China"},{"code":"co","name":"Colombia"},{"code":"cr","name":"Costa Rica"},{"code":"cu","name":"Cuba"},{"code":"cv","name":"Cape Verde"},{"code":"cx","name":"Christmas Island"},{"code":"cy","name":"Cyprus"},{"code":"cz","name":"Czechia"},{"code":"de","name":"Germany"},{"code":"dj","name":"Djibouti"},{"code":"dk","name":"Denmark"},{"code":"dm","name":"Dominica"},{"code":"do","name":"Dominican Republic"},{"code":"dz","name":"Algeria"},{"code":"ec","name":"Ecuador"},{"code":"ee","name":"Estonia"},{"code":"eg","name":"Egypt"},{"code":"eh","name":"Western Sahara"},{"code":"er","name":"Eritrea"},{"code":"es","name":"Spain"},{"code":"et","name":"Ethiopia"},{"code":"fi","name":"Finland"},{"code":"fj","name":"Fiji"},{"code":"fk","name":"Falkland Islands"},{"code":"fm","name":"Micronesia"},{"code":"fo","name":"Faroe Islands"},{"code":"fr","name":"France"},{"code":"ga","name":"Gabon"},{"code":"gb","name":"United Kingdom"},{"code":"gd","name":"Grenada"},{"code":"ge","name":"Georgia"},{"code":"gf","name":"French Guiana"},{"code":"gh","name":"Ghana"},{"code":"gi","name":"Gibraltar"},{"code":"gl","name":"Greenland"},{"code":"gm","name":"Gambia"},{"code":"gn","name":"Guinea"},{"code":"gp","name":"Guadeloupe"},{"code":"gq","name":"Equatorial Guinea"},{"code":"gr","name":"Greece"},{"code":"gs","name":"South Georgia & South Sandwich Islands"},{"code":"gt","name":"Guatemala"},{"code":"gu","name":"Guam"},{"code":"gw","name":"Guinea-Bissau"},{"code":"gy","name":"Guyana"},{"code":"hk","name":"Hong Kong"},{"code":"hm","name":"Heard & McDonald Islands"},{"code":"hn","name":"Honduras"},{"code":"hr","name":"Croatia"},{"code":"ht","name":"Haiti"},{"code":"hu","name":"Hungary"},{"code":"id","name":"Indonesia"},{"code":"ie","name":"Ireland"},{"code":"il","name":"Israel"},{"code":"in","name":"India"},{"code":"io","name":"British Indian Ocean Territory"},{"code":"iq","name":"Iraq"},{"code":"ir","name":"Iran"},{"code":"is","name":"Iceland"},{"code":"it","name":"Italy"},{"code":"jm","name":"Jamaica"},{"code":"jo","name":"Jordan"},{"code":"jp","name":"Japan"},{"code":"ke","name":"Kenya"},{"code":"kg","name":"Kyrgyzstan"},{"code":"kh","name":"Cambodia"},{"code":"ki","name":"Kiribati"},{"code":"km","name":"Comoros"},{"code":"kn","name":"St. Kitts & Nevis"},{"code":"kp","name":"North Korea"},{"code":"kr","name":"South Korea"},{"code":"kw","name":"Kuwait"},{"code":"ky","name":"Cayman Islands"},{"code":"kz","name":"Kazakhstan"},{"code":"la","name":"Laos"},{"code":"lb","name":"Lebanon"},{"code":"lc","name":"St. Lucia"},{"code":"li","name":"Liechtenstein"},{"code":"lk","name":"Sri Lanka"},{"code":"lr","name":"Liberia"},{"code":"ls","name":"Lesotho"},{"code":"lt","name":"Lithuania"},{"code":"lu","name":"Luxembourg"},{"code":"lv","name":"Latvia"},{"code":"ly","name":"Libya"},{"code":"ma","name":"Morocco"},{"code":"mc","name":"Monaco"},{"code":"md","name":"Moldova"},{"code":"me","name":"Montenegro"},{"code":"mg","name":"Madagascar"},{"code":"mh","name":"Marshall Islands"},{"code":"mk","name":"Macedonia"},{"code":"ml","name":"Mali"},{"code":"mm","name":"Myanmar (Burma)"},{"code":"mn","name":"Mongolia"},{"code":"mn","name":"Mongolian Tugrik"},{"code":"mo","name":"Macau"},{"code":"mp","name":"Northern Mariana Islands"},{"code":"mq","name":"Martinique"},{"code":"mr","name":"Mauritania"},{"code":"ms","name":"Montserrat"},{"code":"mt","name":"Malta"},{"code":"mu","name":"Mauritius"},{"code":"mv","name":"Maldives"},{"code":"mw","name":"Malawi"},{"code":"mx","name":"Mexico"},{"code":"my","name":"Malaysia"},{"code":"mz","name":"Mozambique"},{"code":"na","name":"Namibia"},{"code":"nc","name":"New Caledonia"},{"code":"ne","name":"Niger"},{"code":"nf","name":"Norfolk Island"},{"code":"ng","name":"Nigeria"},{"code":"ni","name":"Nicaragua"},{"code":"nl","name":"Netherlands"},{"code":"no","name":"Norway"},{"code":"np","name":"Nepal"},{"code":"nr","name":"Nauru"},{"code":"nu","name":"Niue"},{"code":"nz","name":"New Zealand"},{"code":"om","name":"Oman"},{"code":"pa","name":"Panama"},{"code":"pe","name":"Peru"},{"code":"pf","name":"French Polynesia"},{"code":"pg","name":"Papua New Guinea"},{"code":"ph","name":"Philippines"},{"code":"pk","name":"Pakistan"},{"code":"pl","name":"Poland"},{"code":"pm","name":"St. Pierre & Miquelon"},{"code":"pn","name":"Pitcairn"},{"code":"pr","name":"Puerto Rico"},{"code":"ps","name":"Palestinian Territories"},{"code":"pt","name":"Portugal"},{"code":"pw","name":"Palau"},{"code":"py","name":"Paraguay"},{"code":"qa","name":"Qatar"},{"code":"re","name":"Reunion"},{"code":"ro","name":"Romania"},{"code":"ru","name":"Russia"},{"code":"rw","name":"Rwanda"},{"code":"rs","name":"Serbia"},{"code":"sa","name":"Saudi Arabia"},{"code":"sb","name":"Solomon Islands"},{"code":"sc","name":"Seychelles"},{"code":"sd","name":"Sudan"},{"code":"se","name":"Sweden"},{"code":"sg","name":"Singapore"},{"code":"sh","name":"St. Helena"},{"code":"si","name":"Slovenia"},{"code":"sj","name":"Svalbard & Jan Mayen"},{"code":"sk","name":"Slovakia"},{"code":"sl","name":"Sierra Leone"},{"code":"sm","name":"San Marino"},{"code":"sn","name":"Senegal"},{"code":"so","name":"Somalia"},{"code":"sr","name":"Suriname"},{"code":"ss","name":"South Sudan"},{"code":"st","name":"Sao Tome and Principe"},{"code":"sv","name":"El Salvador"},{"code":"sy","name":"Syria"},{"code":"sz","name":"Swaziland"},{"code":"tc","name":"Turks & Caicos Islands"},{"code":"td","name":"Chad"},{"code":"tf","name":"French Southern Territories"},{"code":"tg","name":"Togo"},{"code":"th","name":"Thailand"},{"code":"tj","name":"Tajikistan"},{"code":"tk","name":"Tokelau"},{"code":"tl","name":"Timor-Leste"},{"code":"tm","name":"Turkmenistan"},{"code":"tn","name":"Tunisia"},{"code":"to","name":"Tonga"},{"code":"tr","name":"Turkey"},{"code":"tt","name":"Trinidad & Tobago"},{"code":"tv","name":"Tuvalu"},{"code":"tw","name":"Taiwan"},{"code":"tz","name":"Tanzania"},{"code":"ua","name":"Ukraine"},{"code":"ug","name":"Uganda"},{"code":"um","name":"U.S. Outlying Islands"},{"code":"us","name":"United States"},{"code":"uy","name":"Uruguay"},{"code":"uz","name":"Uzbekistan"},{"code":"va","name":"Vatican City"},{"code":"vc","name":"St. Vincent & Grenadines"},{"code":"ve","name":"Venezuela"},{"code":"vg","name":"British Virgin Islands"},{"code":"vi","name":"U.S. Virgin Islands"},{"code":"vn","name":"Vietnam"},{"code":"vu","name":"Vanuatu"},{"code":"wf","name":"Wallis & Futuna"},{"code":"ws","name":"Samoa"},{"code":"ye","name":"Yemen"},{"code":"yt","name":"Mayotte"},{"code":"za","name":"South Africa"},{"code":"zm","name":"Zambia"},{"code":"zw","name":"Zimbabwe"}]',
                'countries_json_details_old'       => '[{"symbol":"$","name":"US Dollar","symbol_native":"$","code":"USD","iso":"us","symbol_position":"before"},{"symbol":"€","name":"Euro","symbol_native":"€","code":"EUR","iso":"eu"},{"symbol":"£","name":"British Pound Sterling","symbol_native":"£","code":"GBP","iso":"gb"},{"symbol":"$","name":"Canadian Dollar","symbol_native":"$","code":"CAD","iso":"ca"},{"symbol":"Fr","name":"CFP Franc","symbol_native":"FCFP","code":"XPF","iso":"fr"},{"symbol":"CHF","name":"Swiss Franc","symbol_native":"CHF","code":"CHF","iso":"ch"},{"symbol":"₽","name":"Russian Ruble","symbol_native":"руб.","code":"RUB","iso":"ru"},{"symbol":"¥","name":"Japanese Yen","symbol_native":"￥","code":"JPY","iso":"jp"},{"symbol":"؋","name":"Afghan Afghani","symbol_native":"؋","code":"AFN","iso":"af"},{"symbol":"L","name":"Albanian Lek","symbol_native":"Lek","code":"ALL","iso":"al"},{"symbol":"د.ج","name":"Algerian Dinar","symbol_native":"د.ج.","code":"DZD","iso":"dz"},{"symbol":"$","name":"Argentine Peso","symbol_native":"$","code":"ARS","iso":"ar"},{"symbol":"AMD","name":"Armenian Dram","symbol_native":"դր.","code":"AMD","iso":"am"},{"symbol":"$","name":"Australian Dollar","symbol_native":"$","code":"AUD","iso":"au"},{"symbol":"AZN","name":"Azerbaijani Manat","symbol_native":"ман.","code":"AZN","iso":"az"},{"symbol":".د.ب","name":"Bahraini Dinar","symbol_native":"د.ب.","code":"BHD","iso":"bh"},{"symbol":"৳","name":"Bangladeshi Taka","symbol_native":"৳","code":"BDT","iso":"bd"},{"symbol":"Br","name":"Belarusian Ruble","symbol_native":"BYR","code":"BYR","iso":"by"},{"symbol":"$","name":"Belize Dollar","symbol_native":"$","code":"BZD","iso":"bz"},{"symbol":"Bs.","name":"Bolivian Boliviano","symbol_native":"Bs","code":"BOB","iso":"bo"},{"symbol":"KM","name":"Bosnia-Herzegovina Convertible Mark","symbol_native":"KM","code":"BAM","iso":"ba"},{"symbol":"P","name":"Botswanan Pula","symbol_native":"P","code":"BWP","iso":"bw"},{"symbol":"R$","name":"Brazilian Real","symbol_native":"R$","code":"BRL","iso":"br"},{"symbol":"$","name":"Brunei Dollar","symbol_native":"$","code":"BND","iso":"bn"},{"symbol":"лв.","name":"Bulgarian Lev","symbol_native":"лв.","code":"BGN","iso":"bg"},{"symbol":"Fr","name":"Burundian Franc","symbol_native":"FBu","code":"BIF","iso":"bi"},{"symbol":"៛","name":"Cambodian Riel","symbol_native":"៛","code":"KHR","iso":"kh"},{"symbol":"$","name":"Cape Verdean Escudo","symbol_native":"CV$","code":"CVE","iso":"cv"},{"symbol":"$","name":"Chilean Peso","symbol_native":"$","code":"CLP","iso":"cl"},{"symbol":"¥","name":"Chinese Yuan","symbol_native":"CN¥","code":"CNY","iso":"cn"},{"symbol":"$","name":"Colombian Peso","symbol_native":"$","code":"COP","iso":"co"},{"symbol":"Fr","name":"Comorian Franc","symbol_native":"FC","code":"KMF","iso":"km"},{"symbol":"Fr","name":"Congolese Franc","symbol_native":"FrCD","code":"CDF","iso":"cd"},{"symbol":"₡","name":"Costa Rican Colón","symbol_native":"₡","code":"CRC","iso":"cr"},{"symbol":"kn","name":"Croatian Kuna","symbol_native":"kn","code":"HRK","iso":"hr"},{"symbol":"Kč","name":"Czech Republic Koruna","symbol_native":"Kč","code":"CZK","iso":"cz"},{"symbol":"FCFA","name":"Central African Franc","symbol_native":"FCFA","code":"XAF","iso":"FCFA"},{"symbol":"Dkk","name":"Danish Krone","symbol_native":"kr","code":"DKK","iso":"dk"},{"symbol":"Fr","name":"Djiboutian Franc","symbol_native":"Fdj","code":"DJF","iso":"dj"},{"symbol":"RD$","name":"Dominican Peso","symbol_native":"RD$","code":"DOP","iso":"do"},{"symbol":"EGP","name":"Egyptian Pound","symbol_native":"ج.م.","code":"EGP","iso":"eg"},{"symbol":"Nfk","name":"Eritrean Nakfa","symbol_native":"Nfk","code":"ERN","iso":"er"},{"symbol":"Ekr","name":"Estonian Kroon","symbol_native":"kr","code":"EEK","iso":"ee"},{"symbol":"Br","name":"Ethiopian Birr","symbol_native":"Br","code":"ETB","iso":"et"},{"symbol":"₾","name":"Georgian Lari","symbol_native":"GEL","code":"GEL","iso":"ge"},{"symbol":"₵","name":"Ghanaian Cedi","symbol_native":"GH₵","code":"GHS","iso":"gh"},{"symbol":"Q","name":"Guatemalan Quetzal","symbol_native":"Q","code":"GTQ","iso":"gt"},{"symbol":"Fr","name":"Guinean Franc","symbol_native":"FG","code":"GNF","iso":"gn"},{"symbol":"L","name":"Honduran Lempira","symbol_native":"L","code":"HNL","iso":"hn"},{"symbol":"$","name":"Hong Kong Dollar","symbol_native":"$","code":"HKD","iso":"hk"},{"symbol":"Ft","name":"Hungarian Forint","symbol_native":"Ft","code":"HUF","iso":"hu"},{"symbol":"kr.","name":"Icelandic Króna","symbol_native":"kr","code":"ISK","iso":"is"},{"symbol":"₹","name":"Indian Rupee","symbol_native":"টকা","code":"INR","iso":"in"},{"symbol":"Rp","name":"Indonesian Rupiah","symbol_native":"Rp","code":"IDR","iso":"id"},{"symbol":"﷼","name":"Iranian Rial","symbol_native":"﷼","code":"IRR","iso":"ir"},{"symbol":"د.ع","name":"Iraqi Dinar","symbol_native":"د.ع.","code":"IQD","iso":"iq"},{"symbol":"₪","name":"Israeli New Sheqel","symbol_native":"₪","code":"ILS","iso":"il"},{"symbol":"$","name":"Jamaican Dollar","symbol_native":"$","code":"JMD","iso":"jm"},{"symbol":"د.ا","name":"Jordanian Dinar","symbol_native":"د.أ.","code":"JOD","iso":"jo"},{"symbol":"₸","name":"Kazakhstani Tenge","symbol_native":"тңг.","code":"KZT","iso":"kz"},{"symbol":"KSh","name":"Kenyan Shilling","symbol_native":"Ksh","code":"KES","iso":"ke"},{"symbol":"د.ك","name":"Kuwaiti Dinar","symbol_native":"د.ك.","code":"KWD","iso":"kw"},{"symbol":"Ls","name":"Latvian Lats","symbol_native":"Ls","code":"LVL","iso":"lv"},{"symbol":"ل.ل","name":"Lebanese Pound","symbol_native":"ل.ل.","code":"LBP","iso":"lb"},{"symbol":"ل.د","name":"Libyan Dinar","symbol_native":"د.ل.","code":"LYD","iso":"ly"},{"symbol":"Lt","name":"Lithuanian Litas","symbol_native":"Lt","code":"LTL","iso":"lt"},{"symbol":"P","name":"Macanese Pataca","symbol_native":"MOP$","code":"MOP","iso":"mo"},{"symbol":"ден","name":"Macedonian Denar","symbol_native":"MKD","code":"MKD","iso":"mk"},{"symbol":"₮","name":"Mongolian Tugrik","symbol_native":"MNT","code":"MNT","iso":"mn"},{"symbol":"Ar","name":"Malagasy Ariary","symbol_native":"MGA","code":"MGA","iso":"mg"},{"symbol":"RM","name":"Malaysian Ringgit","symbol_native":"RM","code":"MYR","iso":"my"},{"symbol":"₨","name":"Mauritian Rupee","symbol_native":"MURs","code":"MUR","iso":"mu"},{"symbol":"$","name":"Mexican Peso","symbol_native":"$","code":"MXN","iso":"mx"},{"symbol":"MDL","name":"Moldovan Leu","symbol_native":"MDL","code":"MDL","iso":"md"},{"symbol":"د.م.","name":"Moroccan Dirham","symbol_native":"د.م.","code":"MAD","iso":"ma"},{"symbol":"MT","name":"Mozambican Metical","symbol_native":"MTn","code":"MZN","iso":"mz"},{"symbol":"Ks","name":"Myanma Kyat","symbol_native":"K","code":"MMK","iso":"mm"},{"symbol":"N$","name":"Namibian Dollar","symbol_native":"N$","code":"NAD","iso":"na"},{"symbol":"₨","name":"Nepalese Rupee","symbol_native":"नेरू","code":"NPR","iso":"np"},{"symbol":"NT$","name":"New Taiwan Dollar","symbol_native":"NT$","code":"TWD","iso":"tw"},{"symbol":"$","name":"New Zealand Dollar","symbol_native":"$","code":"NZD","iso":"nz"},{"symbol":"C$","name":"Nicaraguan Córdoba","symbol_native":"C$","code":"NIO","iso":"ni"},{"symbol":"₦","name":"Nigerian Naira","symbol_native":"₦","code":"NGN","iso":"ng"},{"symbol":"kr","name":"Norwegian Krone","symbol_native":"kr","code":"NOK","iso":"no"},{"symbol":"ر.ع.","name":"Omani Rial","symbol_native":"ر.ع.","code":"OMR","iso":"om"},{"symbol":"₨","name":"Pakistani Rupee","symbol_native":"₨","code":"PKR","iso":"pk"},{"symbol":"B\/.","name":"Panamanian Balboa","symbol_native":"B\/.","code":"PAB","iso":"pa"},{"symbol":"₲","name":"Paraguayan Guarani","symbol_native":"₲","code":"PYG","iso":"py"},{"symbol":"S\/","name":"Peruvian Nuevo Sol","symbol_native":"S\/.","code":"PEN","iso":"pe"},{"symbol":"₱","name":"Philippine Peso","symbol_native":"₱","code":"PHP","iso":"ph"},{"symbol":"zł","name":"Polish Zloty","symbol_native":"zł","code":"PLN","iso":"pl"},{"symbol":"ر.ق","name":"Qatari Rial","symbol_native":"ر.ق.","code":"QAR","iso":"qa"},{"symbol":"lei","name":"Romanian Leu","symbol_native":"RON","code":"RON","iso":"ro"},{"symbol":"Fr","name":"Rwandan Franc","symbol_native":"FR","code":"RWF","iso":"rw"},{"symbol":"ر.س","name":"Saudi Riyal","symbol_native":"ر.س.","code":"SAR","iso":"sa"},{"symbol":"рсд","name":"Serbian Dinar","symbol_native":"дин.","code":"RSD","iso":"rs"},{"symbol":"$","name":"Singapore Dollar","symbol_native":"$","code":"SGD","iso":"sg"},{"symbol":"Sh","name":"Somali Shilling","symbol_native":"Ssh","code":"SOS","iso":"so"},{"symbol":"R","name":"South African Rand","symbol_native":"R","code":"ZAR","iso":"za"},{"symbol":"₩","name":"South Korean Won","symbol_native":"₩","code":"KRW","iso":"kr"},{"symbol":"₭","name":"Lao kip","symbol_native":"₭","code":"LAK","iso":"la"},{"symbol":"රු","name":"Sri Lankan Rupee","symbol_native":"SL Re","code":"LKR","iso":"lk"},{"symbol":"ج.س.","name":"Sudanese Pound","symbol_native":"SDG","code":"SDG","iso":"sd"},{"symbol":"kr","name":"Swedish Krona","symbol_native":"kr","code":"SEK","iso":"se"},{"symbol":"ل.س","name":"Syrian Pound","symbol_native":"ل.س.","code":"SYP","iso":"sy"},{"symbol":"Rs","name":"Seychellois Rupee","symbol_native":"SR","code":"SCR","iso":"SCR"},{"symbol":"Sh","name":"Tanzanian Shilling","symbol_native":"TSh","code":"TZS","iso":"tz"},{"symbol":"฿","name":"Thai Baht","symbol_native":"฿","code":"THB","iso":"th"},{"symbol":"T$","name":"Tongan Paʻanga","symbol_native":"T$","code":"TOP","iso":"to"},{"symbol":"$","name":"Trinidad and Tobago Dollar","symbol_native":"$","code":"TTD","iso":"tt"},{"symbol":"د.ت","name":"Tunisian Dinar","symbol_native":"د.ت.","code":"TND","iso":"tn"},{"symbol":"₺","name":"Turkish Lira","symbol_native":"TL","code":"TRY","iso":"tr"},{"symbol":"UGX","name":"Ugandan Shilling","symbol_native":"USh","code":"UGX","iso":"ug"},{"symbol":"₴","name":"Ukrainian Hryvnia","symbol_native":"₴","code":"UAH","iso":"ua"},{"symbol":"د.إ","name":"United Arab Emirates Dirham","symbol_native":"د.إ.","code":"AED","iso":"ae"},{"symbol":"$","name":"Uruguayan Peso","symbol_native":"$","code":"UYU","iso":"uy"},{"symbol":"UZS","name":"Uzbekistan Som","symbol_native":"UZS","code":"UZS","iso":"uz"},{"symbol":"Bs.S.","name":"Venezuelan Bolívar","symbol_native":"Bs.S.","code":"VES","iso":"ve"},{"symbol":"₫","name":"Vietnamese Dong","symbol_native":"₫","code":"VND","iso":"vn"},{"symbol":"﷼","name":"Yemeni Rial","symbol_native":"ر.ي.","code":"YER","iso":"ye"},{"symbol":"ZK","name":"Zambian Kwacha","symbol_native":"ZK","code":"ZMK","iso":"zm"},{"symbol":"$","name":"Cayman Islands dollar","symbol_native":"$","code":"KYD","iso":"ky"},{"symbol":"K","name":"Papua New Guinean Kina","symbol_native":"K","code":"PGK","iso":"pg"}]',
                'countries_json_details'  => '[{"symbol":"$","name":"US Dollar","symbol_native":"$","code":"USD","iso":"us","currency_position":"fixed","symbol_position":"before","decimal_places":2,"thousands_separator":",","decimal_separator":"."},{"symbol":"\u20ac","name":"Euro","symbol_native":"\u20ac","code":"EUR","iso":"eu","currency_position":"","symbol_position":"","decimal_places":2,"thousands_separator":".","decimal_separator":","},{"symbol":"\u00a3","name":"British Pound Sterling","symbol_native":"\u00a3","code":"GBP","iso":"gb","currency_position":"fixed","symbol_position":"before","decimal_places":2,"thousands_separator":",","decimal_separator":"."},{"symbol":"$","name":"Canadian Dollar","symbol_native":"$","code":"CAD","iso":"ca","currency_position":"fixed","symbol_position":"before","decimal_places":2,"thousands_separator":",","decimal_separator":"."},{"symbol":"CHF","name":"Swiss Franc","symbol_native":"CHF","code":"CHF","iso":"ch","currency_position":"fixed","symbol_position":"before","decimal_places":2,"thousands_separator":" ","decimal_separator":"."},{"symbol":"\u20bd","name":"Russian Ruble","symbol_native":"\u0440\u0443\u0431.","code":"RUB","iso":"ru","currency_position":"fixed","symbol_position":"before","decimal_places":2,"thousands_separator":",","decimal_separator":"."},{"symbol":"\u00a5","name":"Japanese Yen","symbol_native":"\uffe5","code":"JPY","iso":"jp","currency_position":"fixed","symbol_position":"before","decimal_places":0,"thousands_separator":",","decimal_separator":"."},{"symbol":"$","name":"Argentine Peso","symbol_native":"$","code":"ARS","iso":"ar","currency_position":"fixed","symbol_position":"before","decimal_places":2,"thousands_separator":".","decimal_separator":","},{"symbol":"$","name":"Australian Dollar","symbol_native":"$","code":"AUD","iso":"au","currency_position":"fixed","symbol_position":"before","decimal_places":2,"thousands_separator":",","decimal_separator":"."},{"symbol":".\u062f.\u0628","name":"Bahraini Dinar","symbol_native":"\u062f.\u0628.","code":"BHD","iso":"bh","currency_position":"fixed","symbol_position":"after","decimal_places":3,"thousands_separator":",","decimal_separator":"."},{"symbol":"\u09f3","name":"Bangladeshi Taka","symbol_native":"\u09f3","code":"BDT","iso":"bd","currency_position":"fixed","symbol_position":"after","decimal_places":2,"thousands_separator":",","decimal_separator":"."},{"symbol":"R$","name":"Brazilian Real","symbol_native":"R$","code":"BRL","iso":"br","currency_position":"fixed","symbol_position":"before","decimal_places":2,"thousands_separator":".","decimal_separator":","},{"symbol":"\u043b\u0432.","name":"Bulgarian Lev","symbol_native":"\u043b\u0432.","code":"BGN","iso":"bg","currency_position":"fixed","symbol_position":"after","decimal_places":2,"thousands_separator":" ","decimal_separator":","},{"symbol":"$","name":"Chilean Peso","symbol_native":"$","code":"CLP","iso":"cl","currency_position":"fixed","symbol_position":"before","decimal_places":0,"thousands_separator":".","decimal_separator":""},{"symbol":"\u00a5","name":"Chinese Yuan","symbol_native":"CN\u00a5","code":"CNY","iso":"cn","currency_position":"fixed","symbol_position":"before","decimal_places":2,"thousands_separator":",","decimal_separator":"."},{"symbol":"$","name":"Colombian Peso","symbol_native":"$","code":"COP","iso":"co","currency_position":"fixed","symbol_position":"before","decimal_places":2,"thousands_separator":".","decimal_separator":","},{"symbol":"kn","name":"Croatian Kuna","symbol_native":"kn","code":"HRK","iso":"hr","currency_position":"fixed","symbol_position":"after","decimal_places":2,"thousands_separator":".","decimal_separator":","},{"symbol":"K\u010d","name":"Czech Republic Koruna","symbol_native":"K\u010d","code":"CZK","iso":"cz","currency_position":"fixed","symbol_position":"after","decimal_places":2,"thousands_separator":" ","decimal_separator":","},{"symbol":"FCFA","name":"Central African Franc","symbol_native":"FCFA","code":"XAF","iso":"FCFA","currency_position":"fixed","symbol_position":"after","decimal_places":2,"thousands_separator":",","decimal_separator":"."},{"symbol":"Dkk","name":"Danish Krone","symbol_native":"kr","code":"DKK","iso":"dk","currency_position":"fixed","symbol_position":"before","decimal_places":2,"thousands_separator":".","decimal_separator":","},{"symbol":"RD$","name":"Dominican Peso","symbol_native":"RD$","code":"DOP","iso":"do","currency_position":"fixed","symbol_position":"before","decimal_places":2,"thousands_separator":",","decimal_separator":"."},{"symbol":"EGP","name":"Egyptian Pound","symbol_native":"\u062c.\u0645.","code":"EGP","iso":"eg","currency_position":"fixed","symbol_position":"before","decimal_places":2,"thousands_separator":",","decimal_separator":"."},{"symbol":"\u20b5","name":"Ghanaian Cedi","symbol_native":"GH\u20b5","code":"GHS","iso":"gh","currency_position":"fixed","symbol_position":"before","decimal_places":2,"thousands_separator":",","decimal_separator":"."},{"symbol":"Q","name":"Guatemalan Quetzal","symbol_native":"Q","code":"GTQ","iso":"gt","currency_position":"fixed","symbol_position":"before","decimal_places":2,"thousands_separator":",","decimal_separator":"."},{"symbol":"$","name":"Hong Kong Dollar","symbol_native":"$","code":"HKD","iso":"hk","currency_position":"fixed","symbol_position":"before","decimal_places":2,"thousands_separator":",","decimal_separator":"."},{"symbol":"Ft","name":"Hungarian Forint","symbol_native":"Ft","code":"HUF","iso":"hu","currency_position":"fixed","symbol_position":"after","decimal_places":2,"thousands_separator":" ","decimal_separator":","},{"symbol":"kr.","name":"Icelandic Kr\u00f3na","symbol_native":"kr","code":"ISK","iso":"is","currency_position":"fixed","symbol_position":"after","decimal_places":0,"thousands_separator":".","decimal_separator":""},{"symbol":"\u20b9","name":"Indian Rupee","symbol_native":"\u099f\u0995\u09be","code":"INR","iso":"in","currency_position":"fixed","symbol_position":"before","decimal_places":2,"thousands_separator":",","decimal_separator":"."},{"symbol":"Rp","name":"Indonesian Rupiah","symbol_native":"Rp","code":"IDR","iso":"id","currency_position":"fixed","symbol_position":"before","decimal_places":2,"thousands_separator":".","decimal_separator":","},{"symbol":"\ufdfc","name":"Iranian Rial","symbol_native":"\ufdfc","code":"IRR","iso":"ir","currency_position":"fixed","symbol_position":"after","decimal_places":2,"thousands_separator":",","decimal_separator":"."},{"symbol":"\u20aa","name":"Israeli New Sheqel","symbol_native":"\u20aa","code":"ILS","iso":"il","currency_position":"fixed","symbol_position":"before","decimal_places":2,"thousands_separator":",","decimal_separator":"."},{"symbol":"KSh","name":"Kenyan Shilling","symbol_native":"Ksh","code":"KES","iso":"ke","currency_position":"fixed","symbol_position":"before","decimal_places":2,"thousands_separator":",","decimal_separator":"."},{"symbol":"\u062f.\u0643","name":"Kuwaiti Dinar","symbol_native":"\u062f.\u0643.","code":"KWD","iso":"kw","currency_position":"fixed","symbol_position":"before","decimal_places":2,"thousands_separator":",","decimal_separator":"."},{"symbol":"\u0644.\u0644","name":"Lebanese Pound","symbol_native":"\u0644.\u0644.","code":"LBP","iso":"lb","currency_position":"fixed","symbol_position":"after","decimal_places":2,"thousands_separator":",","decimal_separator":"."},{"symbol":"RM","name":"Malaysian Ringgit","symbol_native":"RM","code":"MYR","iso":"my","currency_position":"fixed","symbol_position":"before","decimal_places":2,"thousands_separator":",","decimal_separator":"."},{"symbol":"$","name":"Mexican Peso","symbol_native":"$","code":"MXN","iso":"mx","currency_position":"fixed","symbol_position":"before","decimal_places":2,"thousands_separator":",","decimal_separator":"."},{"symbol":"MDL","name":"Moldovan Leu","symbol_native":"MDL","code":"MDL","iso":"md","currency_position":"fixed","symbol_position":"after","decimal_places":2,"thousands_separator":".","decimal_separator":","},{"symbol":"\u062f.\u0645.","name":"Moroccan Dirham","symbol_native":"\u062f.\u0645.","code":"MAD","iso":"ma","currency_position":"fixed","symbol_position":"after","decimal_places":2,"thousands_separator":",","decimal_separator":"."},{"symbol":"NT$","name":"New Taiwan Dollar","symbol_native":"NT$","code":"TWD","iso":"tw","currency_position":"fixed","symbol_position":"before","decimal_places":2,"thousands_separator":",","decimal_separator":"."},{"symbol":"$","name":"New Zealand Dollar","symbol_native":"$","code":"NZD","iso":"nz","currency_position":"fixed","symbol_position":"before","decimal_places":2,"thousands_separator":",","decimal_separator":"."},{"symbol":"\u20a6","name":"Nigerian Naira","symbol_native":"\u20a6","code":"NGN","iso":"ng","currency_position":"fixed","symbol_position":"before","decimal_places":2,"thousands_separator":",","decimal_separator":"."},{"symbol":"kr","name":"Norwegian Krone","symbol_native":"kr","code":"NOK","iso":"no","currency_position":"fixed","symbol_position":"after","decimal_places":2,"thousands_separator":" ","decimal_separator":","},{"symbol":"\u20a8","name":"Pakistani Rupee","symbol_native":"\u20a8","code":"PKR","iso":"pk","currency_position":"fixed","symbol_position":"before","decimal_places":2,"thousands_separator":",","decimal_separator":"."},{"symbol":"S\/","name":"Peruvian Nuevo Sol","symbol_native":"S\/.","code":"PEN","iso":"pe","currency_position":"fixed","symbol_position":"before","decimal_places":2,"thousands_separator":",","decimal_separator":"."},{"symbol":"\u20b1","name":"Philippine Peso","symbol_native":"\u20b1","code":"PHP","iso":"ph","currency_position":"fixed","symbol_position":"before","decimal_places":2,"thousands_separator":",","decimal_separator":"."},{"symbol":"z\u0142","name":"Polish Zloty","symbol_native":"z\u0142","code":"PLN","iso":"pl","currency_position":"fixed","symbol_position":"after","decimal_places":2,"thousands_separator":" ","decimal_separator":","},{"symbol":"lei","name":"Romanian Leu","symbol_native":"RON","code":"RON","iso":"ro","currency_position":"fixed","symbol_position":"after","decimal_places":2,"thousands_separator":".","decimal_separator":","},{"symbol":"\u0631.\u0633","name":"Saudi Riyal","symbol_native":"\u0631.\u0633.","code":"SAR","iso":"sa","currency_position":"fixed","symbol_position":"before","decimal_places":2,"thousands_separator":",","decimal_separator":"."},{"symbol":"\u0440\u0441\u0434","name":"Serbian Dinar","symbol_native":"\u0434\u0438\u043d.","code":"RSD","iso":"rs","currency_position":"fixed","symbol_position":"after","decimal_places":2,"thousands_separator":".","decimal_separator":","},{"symbol":"$","name":"Singapore Dollar","symbol_native":"$","code":"SGD","iso":"sg","currency_position":"fixed","symbol_position":"before","decimal_places":2,"thousands_separator":",","decimal_separator":"."},{"symbol":"R","name":"South African Rand","symbol_native":"R","code":"ZAR","iso":"za","currency_position":"fixed","symbol_position":"before","decimal_places":2,"thousands_separator":" ","decimal_separator":"."},{"symbol":"\u20a9","name":"South Korean Won","symbol_native":"\u20a9","code":"KRW","iso":"kr","currency_position":"fixed","symbol_position":"before","decimal_places":0,"thousands_separator":",","decimal_separator":"."},{"symbol":"\u0dbb\u0dd4","name":"Sri Lankan Rupee","symbol_native":"SL Re","code":"LKR","iso":"lk","currency_position":"fixed","symbol_position":"before","decimal_places":0,"thousands_separator":",","decimal_separator":"."},{"symbol":"kr","name":"Swedish Krona","symbol_native":"kr","code":"SEK","iso":"se","currency_position":"fixed","symbol_position":"after","decimal_places":2,"thousands_separator":" ","decimal_separator":","},{"symbol":"\u0e3f","name":"Thai Baht","symbol_native":"\u0e3f","code":"THB","iso":"th","currency_position":"fixed","symbol_position":"before","decimal_places":2,"thousands_separator":",","decimal_separator":"."},{"symbol":"$","name":"Trinidad and Tobago Dollar","symbol_native":"$","code":"TTD","iso":"tt","currency_position":"fixed","symbol_position":"before","decimal_places":2,"thousands_separator":",","decimal_separator":"."},{"symbol":"\u20ba","name":"Turkish Lira","symbol_native":"TL","code":"TRY","iso":"tr","currency_position":"fixed","symbol_position":"before","decimal_places":2,"thousands_separator":".","decimal_separator":","},{"symbol":"\u20b4","name":"Ukrainian Hryvnia","symbol_native":"\u20b4","code":"UAH","iso":"ua","currency_position":"fixed","symbol_position":"before","decimal_places":2,"thousands_separator":" ","decimal_separator":","},{"symbol":"\u062f.\u0625","name":"United Arab Emirates Dirham","symbol_native":"\u062f.\u0625.","code":"AED","iso":"ae","currency_position":"fixed","symbol_position":"before","decimal_places":2,"thousands_separator":",","decimal_separator":"."},{"symbol":"$","name":"Uruguayan Peso","symbol_native":"$","code":"UYU","iso":"uy","currency_position":"fixed","symbol_position":"before","decimal_places":2,"thousands_separator":".","decimal_separator":","},{"symbol":"\u20ab","name":"Vietnamese Dong","symbol_native":"\u20ab","code":"VND","iso":"vn","currency_position":"fixed","symbol_position":"after","decimal_places":0,"thousands_separator":".","decimal_separator":","}]',
                'appointment_placeholders'     => wp_json_encode(
                    array(
                        array(
                            'value' => '%appointment_date%',
                            'name'  => '%appointment_date%',
                        ),
                        array(
                            'value' => '%appointment_time%',
                            'name'  => '%appointment_time%',
                        ),
                        array(
                            'value' => '%booking_id%',
                            'name'  => '%booking_id%',
                        ),
                        array(
                            'value' => '%payment_method%',
                            'name'  => '%payment_method%',
                        ),
                        array(
                            'value' => '%share_appointment_url%',
                            'name'  => '%share_appointment_url%',
                        ),
                    )
                ),
                'allowed_html' => wp_json_encode(
                    array(
                        'a' => array_merge(
                            $this->affiliatepress_global_attributes(),
                            array(
                                'href' => array(),
                                'rel' => array(),
                                'target' => array(),
                            )
                        ),
                        'b' => $this->affiliatepress_global_attributes(),
                        'br' => $this->affiliatepress_global_attributes(),
                        'center' => array(),
                        'dd' => $this->affiliatepress_global_attributes(),
                        'dl' => $this->affiliatepress_global_attributes(),
                        'dt' => $this->affiliatepress_global_attributes(),
                        'div' => $this->affiliatepress_global_attributes(),
                        'font' => array_merge(
                            $this->affiliatepress_global_attributes(),
                            array(
                                'color' => array(),
                                'face' => array(),
                                'size' => array()
                            )
                        ),
                        'h1' => $this->affiliatepress_global_attributes(),
                        'h2' => $this->affiliatepress_global_attributes(),
                        'h3' => $this->affiliatepress_global_attributes(),
                        'h4' => $this->affiliatepress_global_attributes(),
                        'h5' => $this->affiliatepress_global_attributes(),
                        'h6' => $this->affiliatepress_global_attributes(),
                        'hr' => $this->affiliatepress_global_attributes(),
                        'i' => $this->affiliatepress_global_attributes(),
                        'img' => array_merge(
                            $this->affiliatepress_global_attributes(),
                            array(
                                'alt' => array(),
                                'height' => array(),
                                'src' => array(),
                                'width' => array()
                            )
                        ),
                        'label' => array_merge(
                            $this->affiliatepress_global_attributes(),
                            array(
                                'for' => array(),
                            )
                        ),
                        'line' => array(                        
                            'x1'       => array(),
                            'y1'       => array(),
                            'x2' => array(),
                            'y2' => array(),
                            'stroke' => array(),
                            'stroke-width' => array(),
                            'stroke-linecap' => array(),
                        ),
                        'li' => $this->affiliatepress_global_attributes(),
                        'ol' => $this->affiliatepress_global_attributes(),
                        'optgroup' => $this->affiliatepress_global_attributes(),
                        'p' => $this->affiliatepress_global_attributes(),
                        'span' => $this->affiliatepress_global_attributes(),
                        'strong' => $this->affiliatepress_global_attributes(),
                        'sub' => $this->affiliatepress_global_attributes(),
                        'sup' => $this->affiliatepress_global_attributes(),
                        'svg'        => array(
                            'id'      => array(),
                            'height'  => array(),
                            'width'   => array(),
                            'x'       => array(),
                            'y'       => array(),
                            'xmlns' => array(),
                            'class' => array(),
                            'fill' => array(),
                            'viewBox' => array(),
                        ),  
                        'path'       => array(
                            'id'        => array(),
                            'd'         => array(),
                            'fill'      => array(),
                            'fill-rule' => array(),
                            'clip-rule' => array(),
                            'class'     => array(),
                            'stroke'    => array(),
                            'stroke-width'   => array(),
                            'stroke-opacity' => array(),
                            'stroke-linecap' => array(),
                        ),   
                        'table' => $this->affiliatepress_global_attributes(),
                        'tbody' => $this->affiliatepress_global_attributes(),
                        'thead' => $this->affiliatepress_global_attributes(),
                        'tfooter' => $this->affiliatepress_global_attributes(),
                        'th' => array_merge(
                            $this->affiliatepress_global_attributes(),
                            array(
                                'colspan' => array(),
                                'headers' => array(),
                                'rowspan' => array(),
                                'scope' => array()
                            )
                        ),
                        'td' => array_merge(
                            $this->affiliatepress_global_attributes(),
                            array(
                                'colspan' => array(),
                                'headers' => array(),
                                'rowspan' => array()
                            )
                        ),
                        'tr' => $this->affiliatepress_global_attributes(),
                        'u' => $this->affiliatepress_global_attributes(),
                        'ul' => $this->affiliatepress_global_attributes(),
                    )
                ),
                'allowed_basic_html_tag' => wp_json_encode(array(
                    'span'  => array(
                        'class' => array(),
                        'id'    => array(),
                        'style' => array(),
                        'title' => array(),
                    ),   
                    'div'  => array(
                        'class' => array(),
                        'id'    => array(),
                        'style' => array(),
                        'title' => array(),
                    ),
                    'label' => array(
                        'class' => array(),
                        'id'    => array(),
                        'style' => array(),
                        'for'   => array(),
                     ),
                    'ul' => array(
                        'class' => array(),
                        'id'    => array(),
                        'style' => array(),
                     ),
                    'ol'  => array(
                        'class' => array(),
                        'id'    => array(),
                        'style' => array(),
                    ),
                    'li'  => array(
                        'class' => array(),
                        'id'    => array(),
                        'style' => array(),
                    ),
                    'strong' => array(),
                        'style'=> array(
                        'type' => array(),
                    ),
                    'b' => array(),
                    'i' => array(),
                    'p' => array(
                        'class' => array(),
                        'id'    => array(),
                        'style' => array(),
                    ),
                    'a' => array(
                        'title'  => array(),
                        'href'   => array(),
                        'target' => array(),
                        'class'  => array(),
                        'id'     => array(),
                        'style'  => array(),
                    ),    
                    'h1'  => array(
                        'class' => array(),
                        'id'    => array(),
                        'style' => array(),
                    ),
                    'h2' => array(
                        'class' => array(),
                        'id'    => array(),
                        'style' => array(),
                    ),
                    'h3' => array(
                        'class' => array(),
                        'id'    => array(),
                        'style' => array(),
                    ),
                    'h4' => array(
                        'class' => array(),
                        'id'    => array(),
                        'style' => array(),
                    ),
                    'h5' => array(
                        'class' => array(),
                        'id'    => array(),
                        'style' => array(),
                    ),
                    'h6' => array(
                        'class' => array(),
                        'id'    => array(),
                        'style' => array(),
                    ),
                    'br' => array(),
                    'hr'  => array(
                        'class' => array(),
                        'id'    => array(),
                        'style' => array(),
                    ),                   
                )),
                'affiliate_placeholders'        => wp_json_encode(
                    array(
                        array(
                            'value' => '%affiliate_id%',
                            'name'  => '%affiliate_id%',
                        ),
                        array(
                            'value' => '%affiliate_referral_url%',
                            'name'  => '%affiliate_referral_url%',
                        ),
                        array(
                            'value' => '%affiliate_username%',
                            'name'  => '%affiliate_username%',
                        ),
                        array(
                            'value' => '%affiliate_email%',
                            'name'  => '%affiliate_email%',
                        ),
                        array(
                            'value' => '%affiliate_first_name%',
                            'name'  => '%affiliate_first_name%',
                        ),
                        array(
                            'value' => '%affiliate_last_name%',
                            'name'  => '%affiliate_last_name%',
                        ),
                        array(
                            'value' => '%affiliate_website%',
                            'name'  => '%affiliate_website%',
                        ),
                        array(
                            'value' => '%affiliate_status%',
                            'name'  => '%affiliate_status%',
                        ),                        
                        array(
                            'value' => '%promote_us%',
                            'name'  => '%promote_us%',
                        ),
                        array(
                            'value' => '%company_name%',
                            'name'  => '%company_name%',
                        )                        
                    )
                ),
                'commission_placeholders'        => wp_json_encode(
                    array(
                        array(
                            'value' => '%commission_id%',
                            'name'  => '%commission_id%',
                        ),
                        array(
                            'value' => '%commission_amount%',
                            'name'  => '%commission_amount%',
                        ),
                        array(
                            'value' => '%commission_reference%',
                            'name'  => '%commission_reference%',
                        ),                                                
                    )
                ),
                'payment_placeholders'        => wp_json_encode(
                    array(
                        array(
                            'value' => '%payment_id%',
                            'name'  => '%payment_id%',
                        ),
                        array(
                            'value' => '%payment_amount%',
                            'name'  => '%payment_amount%',
                        ),
                        array(
                            'value' => '%payment_payout_method%',
                            'name'  => '%payment_payout_method%',
                        ),       
                        array(
                            'value' => '%payment_upto_date%',
                            'name'  => '%payment_upto_date%',
                        ),                                            
                    )
                ),                                                                
                'creative_status' => array(
                    array(
                        'value' => '1',
                        'text'  => esc_html__('Active', 'affiliatepress-affiliate-marketing'),
                    ),
                    array(
                        'value' => '0',
                        'text'  => esc_html__('Inactive', 'affiliatepress-affiliate-marketing'),
                    )
                ),
                'commissions_type' => array(
                    array(
                        'value' => 'sale',
                        'text'  => esc_html__('Sale', 'affiliatepress-affiliate-marketing'),
                    ),
                    array(
                        'value' => 'subscription',
                        'text'  => esc_html__('Subscription', 'affiliatepress-affiliate-marketing'),
                    ),
                    array(
                        'value' => 'opt-in',
                        'text'  => esc_html__('Opt-In', 'affiliatepress-affiliate-marketing'),
                    ),                    
                    array(
                        'value' => 'lead',
                        'text'  => esc_html__('Lead', 'affiliatepress-affiliate-marketing'),
                    ),
                    array(
                        'value' => 'performance_bonus',
                        'text'  => esc_html__('Performance Bonus', 'affiliatepress-affiliate-marketing'),
                    ), 
                    array(
                        'value' => 'signup_bonus',
                        'text'  => esc_html__('Signup Bonus', 'affiliatepress-affiliate-marketing'),
                    ),                                        
                ),
                'commissions_status' => array(
                    array(
                        'value' => '1',
                        'text'  => esc_html__('Approved', 'affiliatepress-affiliate-marketing'),
                    ),
                    array(
                        'value' => '2',
                        'text'  => esc_html__('Pending', 'affiliatepress-affiliate-marketing'),
                    ),
                    array(
                        'value' => '3',
                        'text'  => esc_html__('Rejected', 'affiliatepress-affiliate-marketing'),
                    ),
                    array(
                        'value' => '4',
                        'text'  => esc_html__('Paid', 'affiliatepress-affiliate-marketing'),
                    )
                ),
                'payout_types' => array(
                    array(
                        'value' => 'auto',
                        'text'  => esc_html__('Automatic', 'affiliatepress-affiliate-marketing'),
                    ),
                    array(
                        'value' => 'manual',
                        'text'  => esc_html__('Manual', 'affiliatepress-affiliate-marketing'),
                    )
                ),
                'payment_method' => apply_filters('affiliatepress_payment_methods', array(
                        array(
                            'value' => 'manual',
                            'text'  => esc_html__('Manual', 'affiliatepress-affiliate-marketing'),
                        ),
                        array(
                            'value' => 'paypal',
                            'text'  => esc_html__('Paypal', 'affiliatepress-affiliate-marketing'),
                        ),
                    )
                ),    
                'payment_status' => array(
                    array(
                        'value' => '1',
                        'text'  => esc_html__('Unpaid', 'affiliatepress-affiliate-marketing'),
                    ),
                    array(
                        'value' => '2',
                        'text'  => esc_html__('Pending', 'affiliatepress-affiliate-marketing'),
                    ),
                    array(
                        'value' => '3',
                        'text'  => esc_html__('Failed', 'affiliatepress-affiliate-marketing'),
                    ),                                        
                    array(
                        'value' => '4',
                        'text'  => esc_html__('Paid', 'affiliatepress-affiliate-marketing'),
                    ),
                    array(
                        'value' => '5',
                        'text'  => esc_html__('Carry Forward', 'affiliatepress-affiliate-marketing'),
                    ),                                                                              
                ),
                'weekly_cycle_days' => array(
                    array(
                        'value' => '1',
                        'text'  => esc_html__('Monday', 'affiliatepress-affiliate-marketing'),
                    ),
                    array(
                        'value' => '2',
                        'text'  => esc_html__('Tuesday', 'affiliatepress-affiliate-marketing'),
                    ),
                    array(
                        'value' => '3',
                        'text'  => esc_html__('Wednesday', 'affiliatepress-affiliate-marketing'),
                    ),
                    array(
                        'value' => '4',
                        'text'  => esc_html__('Thursday', 'affiliatepress-affiliate-marketing'),
                    ),
                    array(
                        'value' => '5',
                        'text'  => esc_html__('Friday', 'affiliatepress-affiliate-marketing'),
                    ),
                    array(
                        'value' => '6',
                        'text'  => esc_html__('Saturday', 'affiliatepress-affiliate-marketing'),
                    ), 
                    array(
                        'value' => '7',
                        'text'  => esc_html__('Sunday', 'affiliatepress-affiliate-marketing'),
                    )                   
                ),
               'yearly_cycle_months' => array(
                    array(
                        'value' => '1',
                        'text'  => esc_html__('January', 'affiliatepress-affiliate-marketing'),
                    ),
                    array(
                        'value' => '2',
                        'text'  => esc_html__('February', 'affiliatepress-affiliate-marketing'),
                    ),
                    array(
                        'value' => '3',
                        'text'  => esc_html__('March', 'affiliatepress-affiliate-marketing'),
                    ),
                    array(
                        'value' => '4',
                        'text'  => esc_html__('April', 'affiliatepress-affiliate-marketing'),
                    ),
                    array(
                        'value' => '5',
                        'text'  => esc_html__('May', 'affiliatepress-affiliate-marketing'),
                    ),
                    array(
                        'value' => '6',
                        'text'  => esc_html__('June', 'affiliatepress-affiliate-marketing'),
                    ),
                    array(
                        'value' => '7',
                        'text'  => esc_html__('July', 'affiliatepress-affiliate-marketing'),
                    ),
                    array(
                        'value' => '8',
                        'text'  => esc_html__('August', 'affiliatepress-affiliate-marketing'),
                    ),
                    array(
                        'value' => '9',
                        'text'  => esc_html__('September', 'affiliatepress-affiliate-marketing'),
                    ),
                    array(
                        'value' => '10',
                        'text'  => esc_html__('October', 'affiliatepress-affiliate-marketing'),
                    ),
                    array(
                        'value' => '11',
                        'text'  => esc_html__('November', 'affiliatepress-affiliate-marketing'),
                    ),
                    array(
                        'value' => '12',
                        'text'  => esc_html__('December', 'affiliatepress-affiliate-marketing'),
                    ),
                ),
                'url_types' => array(
                    array(
                        'value' => 'affiliate_default_url',
                        'text'  => esc_html__('Affiliate ID (Encoded ID)', 'affiliatepress-affiliate-marketing'),
                    ),
                    array(
                        'value' => 'id',
                        'text'  => esc_html__('Affiliate ID (Plain ID)', 'affiliatepress-affiliate-marketing'),
                    ),
                    array(
                        'value' => 'username',
                        'text'  => esc_html__('WordPress Username', 'affiliatepress-affiliate-marketing'),
                    ),
                    array(
                        'value' => 'md5',
                        'text'  => esc_html__('MD5 Hashed Affiliate ID', 'affiliatepress-affiliate-marketing'),
                    ),      
                ),
                'pagination_val'             => array(
                    array(
                        'text'  => '10',
                        'value' => '10',
                    ),
                    array(
                        'text'  => '25',
                        'value' => '25',
                    ),
                    array(
                        'text'  => '50',
                        'value' => '50',
                    ),
                    array(
                        'text'  => '100',
                        'value' => '100',
                    ),
                    array(
                        'text'  => '250',
                        'value' => '250',
                    ),
                    array(
                        'text'  => '500',
                        'value' => '500',
                    ),
                ),
                'fontend_pagination_val'             => array(
                    array(
                        'text'  => '10',
                        'value' => '10',
                    ),
                    array(
                        'text'  => '25',
                        'value' => '25',
                    ),
                    array(
                        'text'  => '50',
                        'value' => '50',
                    ),
                    array(
                        'text'  => '100',
                        'value' => '100',
                    ),
                    array(
                        'text'  => '250',
                        'value' => '250',
                    ),
                ),
                'integration_priority'=> array(
                    'woocommerce',
                    'armember',
                    'easy_digital_downloads',
                    'bookingpress',
                    'arforms',
                    'memberpress',
                    'ultimate_membership_pro',
                    'restrict_content',
                    'simple_membership',
                    'paid_memberships_pro',
                    'paid_memberships_subscriptions',
                    'lifter_lms',
                    'masteriyo_lms',
                    'learnpress',
                    'learndash',
                    'wp_forms',
                    'ninjaforms',
                    'gravity_forms',
                    'surecart',
                    'wp_easycart',
                    'give_wp',
                    'wp_simple_pay',
                    'accept_stripe_payments',
                    'getpaid',
                    'download_manager'
                ),
            );            
            $affiliatepress_global_data = apply_filters('affiliatepress_add_global_option_data', $affiliatepress_global_data);
            return $affiliatepress_global_data;
        }
        
        /**
         * Function for get all integration plugin list
         *
         * @return void
        */
        function affiliatepress_all_plugin_integration(){

            if (!function_exists('is_plugin_active')) {
                include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
            }

            $affiliatepress_all_plugin_integration = array();

            $affiliatepress_all_plugin_integration[] = array(
                'plugin_name'    => esc_html__('WooCommerce', 'affiliatepress-affiliate-marketing'),
                'plugin_value'   => 'woocommerce',
                'plugin_status'  => (is_plugin_active('woocommerce/woocommerce.php')) ? 1 : 0,
                'plugin_installer' => 'woocommerce/woocommerce.php',
                'plugin_integration_setting_name' => 'enable_woocommerce',
            );

            $affiliatepress_all_plugin_integration[] = array(
                'plugin_name'   => esc_html__('ARMember', 'affiliatepress-affiliate-marketing'),
                'plugin_value'   => 'armember',
                'plugin_status' => (is_plugin_active('armember-membership/armember-membership.php') || is_plugin_active('armember/armember.php')) ? 1 : 0,
                'plugin_installer' => 'armember-membership/armember-membership.php',
                'plugin_pro_installer' => 'armember/armember.php',
                'plugin_integration_setting_name' => 'enable_armember',
            );
            
            $affiliatepress_all_plugin_integration[] = array(
                'plugin_name'   => esc_html__('Easy Digital Downloads', 'affiliatepress-affiliate-marketing'),
                'plugin_value'   => 'easy_digital_downloads',
                'plugin_status' => (is_plugin_active('easy-digital-downloads/easy-digital-downloads.php') || is_plugin_active('easy-digital-downloads-pro/easy-digital-downloads.php')) ? 1 : 0,
                'plugin_installer' => 'easy-digital-downloads/easy-digital-downloads.php',
                'plugin_pro_installer' => 'easy-digital-downloads-pro/easy-digital-downloads.php',
                'plugin_integration_setting_name' => 'enable_easy_digital_downloads',
            ); 

            $affiliatepress_all_plugin_integration[] = array(
                'plugin_name'   => esc_html__('MemberPress', 'affiliatepress-affiliate-marketing'),
                'plugin_value'   => 'memberpress',
                'plugin_status' => (is_plugin_active('memberpress/memberpress.php') ) ? 1 : 0,
                'plugin_installer' => 'memberpress/memberpress.php',
                'plugin_integration_setting_name' => 'enable_memberpress',
            );
            

            $affiliatepress_all_plugin_integration[] = array(
                'plugin_name'   => esc_html__('Restrict Content pro', 'affiliatepress-affiliate-marketing'),
                'plugin_value'   => 'restrict_content',
                'plugin_status' => (is_plugin_active('restrict-content/restrictcontent.php') || is_plugin_active('restrict-content-pro/restrict-content-pro.php')) ? 1 : 0,
                'plugin_installer' => 'restrict-content/restrictcontent.php',
                'plugin_pro_installer' => 'restrict-content-pro/restrict-content-pro.php',
                'plugin_integration_setting_name' => 'enable_restrict_content',
            );

            $affiliatepress_all_plugin_integration[] = array(
                'plugin_name'   => esc_html__('SureCart', 'affiliatepress-affiliate-marketing'),
                'plugin_value'   => 'surecart',
                'plugin_status' => (is_plugin_active('surecart/surecart.php')) ? 1 : 0,
                'plugin_installer' => 'surecart/surecart.php',
                'plugin_integration_setting_name' => 'enable_surecart',
            );
            $affiliatepress_all_plugin_integration[] = array(
                'plugin_name'   => esc_html__('WP EasyCart', 'affiliatepress-affiliate-marketing'),
                'plugin_value'   => 'wp_easycart',
                'plugin_status' => (is_plugin_active('wp-easycart/wpeasycart.php')) ? 1 : 0,
                'plugin_installer' => 'wp-easycart/wpeasycart.php',
                'plugin_integration_setting_name' => 'enable_wp_easycart',
            );

            $affiliatepress_all_plugin_integration[] = array(
                'plugin_name'   => esc_html__('LifterLMS', 'affiliatepress-affiliate-marketing'),
                'plugin_value'   => 'lifter_lms',
                'plugin_status' => (is_plugin_active('lifterlms/lifterlms.php')) ? 1 : 0,
                'plugin_installer' => 'lifterlms/lifterlms.php',
                'plugin_integration_setting_name' => 'enable_lifter_lms',
            );

            $affiliatepress_all_plugin_integration[] = array(
                'plugin_name'   => esc_html__('GiveWP', 'affiliatepress-affiliate-marketing'),
                'plugin_value'   => 'give_wp',
                'plugin_status' => (is_plugin_active('give/give.php')) ? 1 : 0,
                'plugin_installer' => 'give/give.php',
                'plugin_integration_setting_name' => 'enable_give_wp',
            );          
            
            $affiliatepress_all_plugin_integration[] = array(
                'plugin_name'    => esc_html__('Ninja Forms', 'affiliatepress-affiliate-marketing'),
                'plugin_value'   => 'ninjaforms',
                'plugin_status'  => (is_plugin_active('ninja-forms/ninja-forms.php')) ? 1 : 0,
                'plugin_installer' => 'ninja-forms/ninja-forms.php',
                'plugin_integration_setting_name' => 'enable_ninjaforms',
            ); 

            $affiliatepress_all_plugin_integration[] = array(
                'plugin_name'   => esc_html__('WPForms', 'affiliatepress-affiliate-marketing'),
                'plugin_value'   => 'wp_forms',
                'plugin_status' => (is_plugin_active('wpforms/wpforms.php')) ? 1 : 0,
                'plugin_installer' => 'wpforms/wpforms.php',
                'plugin_integration_setting_name' => 'enable_wp_forms',
            );

            $affiliatepress_all_plugin_integration[] = array(
                'plugin_name'   => esc_html__('Simple Membership', 'affiliatepress-affiliate-marketing'),
                'plugin_value'   => 'simple_membership',
                'plugin_status' => (is_plugin_active('simple-membership/simple-wp-membership.php')) ? 1 : 0,
                'plugin_installer' => 'simple-membership/simple-wp-membership.php',
                'plugin_integration_setting_name' => 'enable_simple_membership',
            );

            $affiliatepress_all_plugin_integration[] = array(
                'plugin_name'   => esc_html__('Paid Memberships Pro', 'affiliatepress-affiliate-marketing'),
                'plugin_value'   => 'paid_memberships_pro',
                'plugin_status' => (is_plugin_active('paid-memberships-pro/paid-memberships-pro.php')) ? 1 : 0,
                'plugin_installer' => 'paid-memberships-pro/paid-memberships-pro.php',
                'plugin_integration_setting_name' => 'enable_paid_memberships_pro',
            );    
            
            $affiliatepress_all_plugin_integration[] = array(
                'plugin_name'   => esc_html__('Paid Member Subscriptions', 'affiliatepress-affiliate-marketing'),
                'plugin_value'  => 'paid_memberships_subscriptions',
                'plugin_status' => (is_plugin_active('paid-member-subscriptions/index.php')) ? 1 : 0,
                'plugin_installer' => 'paid-member-subscriptions/index.php',
                'plugin_integration_setting_name' => 'enable_paid_memberships_subscriptions',
            );
            
            $affiliatepress_all_plugin_integration[] = array(
                'plugin_name'   => esc_html__('Gravity Forms', 'affiliatepress-affiliate-marketing'),
                'plugin_value'  => 'gravity_forms',
                'plugin_status' => (is_plugin_active('gravityforms/gravityforms.php')) ? 1 : 0,
                'plugin_installer' => 'gravityforms/gravityforms.php',
                'plugin_integration_setting_name' => 'enable_gravity_forms',
            );
            
            $affiliatepress_all_plugin_integration[] = array(
                'plugin_name'   => esc_html__('WP Simple Pay', 'affiliatepress-affiliate-marketing'),
                'plugin_value'  => 'wp_simple_pay',
                'plugin_status' => (is_plugin_active('stripe/stripe-checkout.php')) ? 1 : 0,
                'plugin_installer' => 'stripe/stripe-checkout.php',
                'plugin_integration_setting_name' => 'enable_wp_simple_pay',
            );            
            
            $affiliatepress_all_plugin_integration[] = array(
                'plugin_name'   => esc_html__('Masteriyo LMS', 'affiliatepress-affiliate-marketing'),
                'plugin_value'  => 'masteriyo_lms',
                'plugin_status' => (is_plugin_active('learning-management-system/lms.php') || is_plugin_active('learning-management-system-pro/lms.php')) ? 1 : 0,
                'plugin_installer' => 'learning-management-system/lms.php',
                'plugin_pro_installer' => 'learning-management-system-pro/lms.php',
                'plugin_integration_setting_name' => 'enable_masteriyo_lms',
            );    
            
            $affiliatepress_all_plugin_integration[] = array(
                'plugin_name'   => esc_html__('LearnPress', 'affiliatepress-affiliate-marketing'),
                'plugin_value'  => 'learnpress',
                'plugin_status' => (is_plugin_active('learnpress/learnpress.php')) ? 1 : 0,
                'plugin_installer' => 'learnpress/learnpress.php',
                'plugin_integration_setting_name' => 'enable_learnpress',
            );    

            $affiliatepress_all_plugin_integration[] = array(
                'plugin_name'   => esc_html__('Accept Stripe Payments', 'affiliatepress-affiliate-marketing'),
                'plugin_value'  => 'accept_stripe_payments',
                'plugin_status' => (is_plugin_active('stripe-payments/accept-stripe-payments.php')) ? 1 : 0,
                'plugin_installer' => 'stripe-payments/accept-stripe-payments.php',
                'plugin_integration_setting_name' => 'enable_accept_stripe_payments',
            );   	    

            $affiliatepress_all_plugin_integration[] = array(
                'plugin_name'   => esc_html__('GetPaid', 'affiliatepress-affiliate-marketing'),
                'plugin_value'  => 'getpaid',
                'plugin_status' => (is_plugin_active('invoicing/invoicing.php')) ? 1 : 0,
                'plugin_installer' => 'invoicing/invoicing.php',
                'plugin_integration_setting_name' => 'enable_getpaid',
            );
            
            $affiliatepress_all_plugin_integration[] = array(
                'plugin_name'   => esc_html__('Ultimate Membership Pro', 'affiliatepress-affiliate-marketing'),
                'plugin_value'  => 'ultimate_membership_pro',
                'plugin_status' => (is_plugin_active('indeed-membership-pro/indeed-membership-pro.php')) ? 1 : 0,
                'plugin_installer' => 'indeed-membership-pro/indeed-membership-pro.php',
                'plugin_integration_setting_name' => 'enable_ultimate_membership_pro',
            );

            $affiliatepress_all_plugin_integration[] = array(
                'plugin_name'    => esc_html__('ARForms', 'affiliatepress-affiliate-marketing'),
                'plugin_value'   => 'arforms',
                'plugin_status'  => (is_plugin_active('arforms/arforms.php')) ? 1 : 0,
                'plugin_installer' => 'arforms/arforms.php',
                'plugin_integration_setting_name' => 'enable_arforms',
            );

            $affiliatepress_all_plugin_integration[] = array(
                'plugin_name'    => esc_html__('Download Manager', 'affiliatepress-affiliate-marketing'),
                'plugin_value'   => 'download_manager',
                'plugin_status'  => (is_plugin_active('wpdm-premium-packages/wpdm-premium-packages.php')) ? 1 : 0,
                'plugin_installer' => 'wpdm-premium-packages/wpdm-premium-packages.php',
                'plugin_integration_setting_name' => 'enable_download_manager',
            );

            $affiliatepress_all_plugin_integration[] = array(
                'plugin_name'    => esc_html__('BookingPress', 'affiliatepress-affiliate-marketing'),
                'plugin_value'   => 'bookingpress',
                'plugin_status'  => (is_plugin_active('bookingpress-appointment-booking-pro/bookingpress-appointment-booking-pro.php')) ? 1 : 0,
                'plugin_installer' => 'bookingpress-appointment-booking-pro/bookingpress-appointment-booking-pro.php',
                'plugin_integration_setting_name' => 'enable_bookingpress',
            );

            $affiliatepress_all_plugin_integration[] = array(
                'plugin_name'   => esc_html__('LearnDash', 'affiliatepress-affiliate-marketing'),
                'plugin_value'  => 'learndash',
                'plugin_status' => (is_plugin_active('sfwd-lms/sfwd_lms.php')) ? 1 : 0,
                'plugin_installer' => 'sfwd-lms/sfwd_lms.php',
                'plugin_integration_setting_name' => 'enable_learndash',
            );    

            $affiliatepress_all_plugin_integration = apply_filters( 'affiliatepress_add_other_integration', $affiliatepress_all_plugin_integration);

            return $affiliatepress_all_plugin_integration;
        }

        function affiliatepress_priority_wise_integration_get() {

            $affiliatepress_options = $this->affiliatepress_global_options();
            $affiliatepress_priority_array = isset($affiliatepress_options['integration_priority'])  ? $affiliatepress_options['integration_priority'] : array();
            $affiliatepress_all_integrations = $this->affiliatepress_all_plugin_integration();

            $affiliatepress_integration_map = array();
            foreach ($affiliatepress_all_integrations as $affiliatepress_integration) {
                $affiliatepress_integration_map[$affiliatepress_integration['plugin_value']] = $affiliatepress_integration;
            }
        
            if (!function_exists('is_plugin_active')) {
                include_once(ABSPATH . 'wp-admin/includes/plugin.php');
            }
            foreach ($affiliatepress_priority_array as $affiliatepress_priority_value) {
        
                if (isset($affiliatepress_integration_map[$affiliatepress_priority_value])) {
        
                    $affiliatepress_integration = $affiliatepress_integration_map[$affiliatepress_priority_value];
                    $affiliatepress_is_active = false;
        
                    if (!empty($affiliatepress_integration['plugin_installer']) && is_plugin_active($affiliatepress_integration['plugin_installer'])) {
                        $affiliatepress_is_active = true;
                    }
        
                    if (!empty($affiliatepress_integration['plugin_pro_installer']) &&  is_plugin_active($affiliatepress_integration['plugin_pro_installer'])) {
                        $affiliatepress_is_active = true;
                    }
        
                    if ($affiliatepress_is_active) {
                        return $affiliatepress_priority_value; 
                    }
                }
            }
        
            return ''; 
        }
        
        function affiliatepress_global_attributes(){
            return array(
                'class' => array(),
                'id' => array(),
                'title' => array(),
                'style' => array(),
            );
        }

        /**
         * Function for return BookingPress Default Fonts
         *
         * @return void
        */
        function affiliatepress_get_default_fonts(){
            return array(
                'Inter',
                'Arial',
                'Helvetica',
                'sans-serif',
                'Lucida Grande',
                'Lucida Sans Unicode',
                'Tahoma',
                'Times New Roman',
                'Courier New',
                'Verdana',
                'Geneva',
                'Courier',
                'Monospace',
                'Times',
                'Open Sans Semibold',
                'Open Sans Bold',
            );
        }

        /**
         * Function for return all supported google fonts
         *
         * @return void
         */
        function affiliatepress_get_google_fonts()
        {
            return array(
            'ABeeZee',
            'Abel',
            'Abhaya Libre',
            'Abril Fatface',
            'Aclonica',
            'Acme',
            'Actor',
            'Adamina',
            'Advent Pro',
            'Aguafina Script',
            'Akronim',
            'Aladin',
            'Aldrich',
            'Alef',
            'Alegreya',
            'Alegreya SC',
            'Alegreya Sans',
            'Alegreya Sans SC',
            'Alex Brush',
            'Alfa Slab One',
            'Alice',
            'Alike',
            'Alike Angular',
            'Allan',
            'Allerta',
            'Allerta Stencil',
            'Allura',
            'Almendra',
            'Almendra Display',
            'Almendra SC',
            'Amarante',
            'Amaranth',
            'Amatic SC',
            'Amethysta',
            'Amiko',
            'Amiri',
            'Amita',
            'Anaheim',
            'Andada',
            'Andika',
            'Angkor',
            'Annie Use Your Telescope',
            'Anonymous Pro',
            'Antic',
            'Antic Didone',
            'Antic Slab',
            'Anton',
            'Arapey',
            'Arbutus',
            'Arbutus Slab',
            'Architects Daughter',
            'Archivo',
            'Archivo Black',
            'Archivo Narrow',
            'Aref Ruqaa',
            'Arima Madurai',
            'Arimo',
            'Arizonia',
            'Armata',
            'Arsenal',
            'Artifika',
            'Arvo',
            'Arya',
            'Asap',
            'Asap Condensed',
            'Asar',
            'Asset',
            'Assistant',
            'Astloch',
            'Asul',
            'Athiti',
            'Atma',
            'Atomic Age',
            'Aubrey',
            'Audiowide',
            'Autour One',
            'Average',
            'Average Sans',
            'Averia Gruesa Libre',
            'Averia Libre',
            'Averia Sans Libre',
            'Averia Serif Libre',
            'Bad Script',
            'Bahiana',
            'Bai Jamjuree',
            'Baloo',
            'Baloo Bhai',
            'Baloo Bhaijaan',
            'Baloo Bhaina',
            'Baloo Chettan',
            'Baloo Da',
            'Baloo Paaji',
            'Baloo Tamma',
            'Baloo Tammudu',
            'Baloo Thambi',
            'Balthazar',
            'Bangers',
            'Barlow',
            'Barlow Condensed',
            'Barlow Semi Condensed',
            'Barrio',
            'Basic',
            'Battambang',
            'Baumans',
            'Bayon',
            'Belgrano',
            'Bellefair',
            'Belleza',
            'BenchNine',
            'Bentham',
            'Berkshire Swash',
            'Bevan',
            'Bigelow Rules',
            'Bigshot One',
            'Bilbo',
            'Bilbo Swash Caps',
            'BioRhyme',
            'BioRhyme Expanded',
            'Biryani',
            'Bitter',
            'Black And White Picture',
            'Black Han Sans',
            'Black Ops One',
            'Bokor',
            'Bonbon',
            'Boogaloo',
            'Bowlby One',
            'Bowlby One SC',
            'Brawler',
            'Bree Serif',
            'Bubblegum Sans',
            'Bubbler One',
            'Buda',
            'Buenard',
            'Bungee',
            'Bungee Hairline',
            'Bungee Inline',
            'Bungee Outline',
            'Bungee Shade',
            'Butcherman',
            'Butterfly Kids',
            'Cabin',
            'Cabin Condensed',
            'Cabin Sketch',
            'Caesar Dressing',
            'Cagliostro',
            'Cairo',
            'Calligraffitti',
            'Cambay',
            'Cambo',
            'Candal',
            'Cantarell',
            'Cantata One',
            'Cantora One',
            'Capriola',
            'Cardo',
            'Carme',
            'Carrois Gothic',
            'Carrois Gothic SC',
            'Carter One',
            'Catamaran',
            'Caudex',
            'Caveat',
            'Caveat Brush',
            'Cedarville Cursive',
            'Ceviche One',
            'Chakra Petch',
            'Changa',
            'Changa One',
            'Chango',
            'Charmonman',
            'Chathura',
            'Chau Philomene One',
            'Chela One',
            'Chelsea Market',
            'Chenla',
            'Cherry Cream Soda',
            'Cherry Swash',
            'Chewy',
            'Chicle',
            'Chivo',
            'Chonburi',
            'Cinzel',
            'Cinzel Decorative',
            'Clicker Script',
            'Coda',
            'Coda Caption',
            'Codystar',
            'Coiny',
            'Combo',
            'Comfortaa',
            'Coming Soon',
            'Concert One',
            'Condiment',
            'Content',
            'Contrail One',
            'Convergence',
            'Cookie',
            'Copse',
            'Corben',
            'Cormorant',
            'Cormorant Garamond',
            'Cormorant Infant',
            'Cormorant SC',
            'Cormorant Unicase',
            'Cormorant Upright',
            'Courgette',
            'Cousine',
            'Coustard',
            'Covered By Your Grace',
            'Crafty Girls',
            'Creepster',
            'Crete Round',
            'Crimson Text',
            'Croissant One',
            'Crushed',
            'Cuprum',
            'Cute Font',
            'Cutive',
            'Cutive Mono',
            'Damion',
            'Dancing Script',
            'Dangrek',
            'David Libre',
            'Dawning of a New Day',
            'Days One',
            'Dekko',
            'Delius',
            'Delius Swash Caps',
            'Delius Unicase',
            'Della Respira',
            'Denk One',
            'Devonshire',
            'Dhurjati',
            'Didact Gothic',
            'Diplomata',
            'Diplomata SC',
            'Do Hyeon',
            'Dokdo',
            'Domine',
            'Donegal One',
            'Doppio One',
            'Dorsa',
            'Dosis',
            'Dr Sugiyama',
            'Duru Sans',
            'Dynalight',
            'EB Garamond',
            'Eagle Lake',
            'East Sea Dokdo',
            'Eater',
            'Economica',
            'Eczar',
            'El Messiri',
            'Electrolize',
            'Elsie',
            'Elsie Swash Caps',
            'Emblema One',
            'Emilys Candy',
            'Encode Sans',
            'Encode Sans Condensed',
            'Encode Sans Expanded',
            'Encode Sans Semi Condensed',
            'Encode Sans Semi Expanded',
            'Engagement',
            'Englebert',
            'Enriqueta',
            'Erica One',
            'Esteban',
            'Euphoria Script',
            'Ewert',
            'Exo',
            'Exo 2',
            'Expletus Sans',
            'Fahkwang',
            'Fanwood Text',
            'Farsan',
            'Fascinate',
            'Fascinate Inline',
            'Faster One',
            'Fasthand',
            'Fauna One',
            'Faustina',
            'Federant',
            'Federo',
            'Felipa',
            'Fenix',
            'Finger Paint',
            'Fira Mono',
            'Fira Sans',
            'Fira Sans Condensed',
            'Fira Sans Extra Condensed',
            'Fjalla One',
            'Fjord One',
            'Flamenco',
            'Flavors',
            'Fondamento',
            'Fontdiner Swanky',
            'Forum',
            'Francois One',
            'Frank Ruhl Libre',
            'Freckle Face',
            'Fredericka the Great',
            'Fredoka One',
            'Freehand',
            'Fresca',
            'Frijole',
            'Fruktur',
            'Fugaz One',
            'GFS Didot',
            'GFS Neohellenic',
            'Gabriela',
            'Gaegu',
            'Gafata',
            'Galada',
            'Galdeano',
            'Galindo',
            'Gamja Flower',
            'Gentium Basic',
            'Gentium Book Basic',
            'Geo',
            'Geostar',
            'Geostar Fill',
            'Germania One',
            'Gidugu',
            'Gilda Display',
            'Give You Glory',
            'Glass Antiqua',
            'Glegoo',
            'Gloria Hallelujah',
            'Goblin One',
            'Gochi Hand',
            'Gorditas',
            'Gothic A1',
            'Goudy Bookletter 1911',
            'Graduate',
            'Grand Hotel',
            'Gravitas One',
            'Great Vibes',
            'Griffy',
            'Gruppo',
            'Gudea',
            'Gugi',
            'Gurajada',
            'Habibi',
            'Halant',
            'Hammersmith One',
            'Hanalei',
            'Hanalei Fill',
            'Handlee',
            'Hanuman',
            'Happy Monkey',
            'Harmattan',
            'Headland One',
            'Heebo',
            'Henny Penny',
            'Herr Von Muellerhoff',
            'Hi Melody',
            'Hind',
            'Hind Guntur',
            'Hind Madurai',
            'Hind Siliguri',
            'Hind Vadodara',
            'Holtwood One SC',
            'Homemade Apple',
            'Homenaje',
            'IBM Plex Mono',
            'IBM Plex Sans',
            'IBM Plex Sans Condensed',
            'IBM Plex Serif',
            'IM Fell DW Pica',
            'IM Fell DW Pica SC',
            'IM Fell Double Pica',
            'IM Fell Double Pica SC',
            'IM Fell English',
            'IM Fell English SC',
            'IM Fell French Canon',
            'IM Fell French Canon SC',
            'IM Fell Great Primer',
            'IM Fell Great Primer SC',
            'Iceberg',
            'Iceland',
            'Imprima',
            'Inconsolata',
            'Inder',
            'Indie Flower',
            'Inika',
            'Inknut Antiqua',
            'Irish Grover',
            'Istok Web',
            'Italiana',
            'Italianno',
            'Itim',
            'Jacques Francois',
            'Jacques Francois Shadow',
            'Jaldi',
            'Jim Nightshade',
            'Jockey One',
            'Jolly Lodger',
            'Jomhuria',
            'Josefin Sans',
            'Josefin Slab',
            'Joti One',
            'Jua',
            'Judson',
            'Julee',
            'Julius Sans One',
            'Junge',
            'Jura',
            'Just Another Hand',
            'Just Me Again Down Here',
            'K2D',
            'Kadwa',
            'Kalam',
            'Kameron',
            'Kanit',
            'Kantumruy',
            'Karla',
            'Karma',
            'Katibeh',
            'Kaushan Script',
            'Kavivanar',
            'Kavoon',
            'Kdam Thmor',
            'Keania One',
            'Kelly Slab',
            'Kenia',
            'Khand',
            'Khmer',
            'Khula',
            'Kirang Haerang',
            'Kite One',
            'Knewave',
            'KoHo',
            'Kodchasan',
            'Kosugi',
            'Kosugi Maru',
            'Kotta One',
            'Koulen',
            'Kranky',
            'Kreon',
            'Kristi',
            'Krona One',
            'Krub',
            'Kumar One',
            'Kumar One Outline',
            'Kurale',
            'La Belle Aurore',
            'Laila',
            'Lakki Reddy',
            'Lalezar',
            'Lancelot',
            'Lateef',
            'Lato',
            'League Script',
            'Leckerli One',
            'Ledger',
            'Lekton',
            'Lemon',
            'Lemonada',
            'Life Savers',
            'Lilita One',
            'Lily Script One',
            'Limelight',
            'Linden Hill',
            'Lobster',
            'Lobster Two',
            'Londrina Outline',
            'Londrina Shadow',
            'Londrina Sketch',
            'Londrina Solid',
            'Lora',
            'Love Ya Like A Sister',
            'Loved by the King',
            'Lovers Quarrel',
            'Luckiest Guy',
            'Lusitana',
            'Lustria',
            'M PLUS 1p',
            'M PLUS Rounded 1c',
            'Macondo',
            'Macondo Swash Caps',
            'Mada',
            'Magra',
            'Maiden Orange',
            'Maitree',
            'Mako',
            'Mali',
            'Mallanna',
            'Mandali',
            'Manuale',
            'Marcellus',
            'Marcellus SC',
            'Marck Script',
            'Margarine',
            'Markazi Text',
            'Marko One',
            'Marmelad',
            'Martel',
            'Martel Sans',
            'Marvel',
            'Mate',
            'Mate SC',
            'Maven Pro',
            'McLaren',
            'Meddon',
            'MedievalSharp',
            'Medula One',
            'Meera Inimai',
            'Megrim',
            'Meie Script',
            'Merienda',
            'Merienda One',
            'Merriweather',
            'Merriweather Sans',
            'Metal',
            'Metal Mania',
            'Metamorphous',
            'Metrophobic',
            'Michroma',
            'Milonga',
            'Miltonian',
            'Miltonian Tattoo',
            'Mina',
            'Miniver',
            'Miriam Libre',
            'Mirza',
            'Miss Fajardose',
            'Mitr',
            'Modak',
            'Modern Antiqua',
            'Mogra',
            'Molengo',
            'Molle',
            'Monda',
            'Monofett',
            'Monoton',
            'Monsieur La Doulaise',
            'Montaga',
            'Montez',
            'Montserrat',
            'Montserrat Alternates',
            'Montserrat Subrayada',
            'Moul',
            'Moulpali',
            'Mountains of Christmas',
            'Mouse Memoirs',
            'Mr Bedfort',
            'Mr Dafoe',
            'Mr De Haviland',
            'Mrs Saint Delafield',
            'Mrs Sheppards',
            'Mukta',
            'Mukta Mahee',
            'Mukta Malar',
            'Mukta Vaani',
            'Muli',
            'Mystery Quest',
            'Manrope',
            'NTR',
            'Nanum Brush Script',
            'Nanum Gothic',
            'Nanum Gothic Coding',
            'Nanum Myeongjo',
            'Nanum Pen Script',
            'Neucha',
            'Neuton',
            'New Rocker',
            'News Cycle',
            'Niconne',
            'Niramit',
            'Nixie One',
            'Nobile',
            'Nokora',
            'Norican',
            'Nosifer',
            'Notable',
            'Nothing You Could Do',
            'Noticia Text',
            'Noto Sans',
            'Noto Sans JP',
            'Noto Sans KR',
            'Noto Serif',
            'Noto Serif JP',
            'Noto Serif KR',
            'Nova Cut',
            'Nova Flat',
            'Nova Mono',
            'Nova Oval',
            'Nova Round',
            'Nova Script',
            'Nova Slim',
            'Nova Square',
            'Numans',
            'Nunito',
            'Nunito Sans',
            'Odor Mean Chey',
            'Offside',
            'Old Standard TT',
            'Oldenburg',
            'Oleo Script',
            'Oleo Script Swash Caps',
            'Open Sans',
            'Open Sans Condensed',
            'Oranienbaum',
            'Orbitron',
            'Oregano',
            'Orienta',
            'Original Surfer',
            'Oswald',
            'Over the Rainbow',
            'Overlock',
            'Overlock SC',
            'Overpass',
            'Overpass Mono',
            'Ovo',
            'Oxygen',
            'Oxygen Mono',
            'PT Mono',
            'PT Sans',
            'PT Sans Caption',
            'PT Sans Narrow',
            'PT Serif',
            'PT Serif Caption',
            'Pacifico',
            'Padauk',
            'Palanquin',
            'Palanquin Dark',
            'Pangolin',
            'Paprika',
            'Parisienne',
            'Passero One',
            'Passion One',
            'Pathway Gothic One',
            'Patrick Hand',
            'Patrick Hand SC',
            'Pattaya',
            'Patua One',
            'Pavanam',
            'Paytone One',
            'Peddana',
            'Peralta',
            'Permanent Marker',
            'Petit Formal Script',
            'Petrona',
            'Philosopher',
            'Piedra',
            'Pinyon Script',
            'Pirata One',
            'Plaster',
            'Play',
            'Playball',
            'Playfair Display',
            'Playfair Display SC',
            'Podkova',
            'Poiret One',
            'Poller One',
            'Poly',
            'Pompiere',
            'Pontano Sans',
            'Poor Story',
            'Poppins',
            'Port Lligat Sans',
            'Port Lligat Slab',
            'Pragati Narrow',
            'Prata',
            'Preahvihear',
            'Press Start 2P',
            'Pridi',
            'Princess Sofia',
            'Prociono',
            'Prompt',
            'Prosto One',
            'Proza Libre',
            'Puritan',
            'Purple Purse',
            'Quando',
            'Quantico',
            'Quattrocento',
            'Quattrocento Sans',
            'Questrial',
            'Quicksand',
            'Quintessential',
            'Qwigley',
            'Racing Sans One',
            'Radley',
            'Rajdhani',
            'Rakkas',
            'Raleway',
            'Raleway Dots',
            'Ramabhadra',
            'Ramaraja',
            'Rambla',
            'Rammetto One',
            'Ranchers',
            'Rancho',
            'Ranga',
            'Rasa',
            'Rationale',
            'Ravi Prakash',
            'Redressed',
            'Reem Kufi',
            'Reenie Beanie',
            'Revalia',
            'Rhodium Libre',
            'Ribeye',
            'Ribeye Marrow',
            'Righteous',
            'Risque',
            'Roboto',
            'Roboto Condensed',
            'Roboto Mono',
            'Roboto Slab',
            'Rochester',
            'Rock Salt',
            'Rokkitt',
            'Romanesco',
            'Ropa Sans',
            'Rosario',
            'Rosarivo',
            'Rouge Script',
            'Rozha One',
            'Rubik',
            'Rubik Mono One',
            'Ruda',
            'Rufina',
            'Ruge Boogie',
            'Ruluko',
            'Rum Raisin',
            'Ruslan Display',
            'Russo One',
            'Ruthie',
            'Rye',
            'Sacramento',
            'Sahitya',
            'Sail',
            'Saira',
            'Saira Condensed',
            'Saira Extra Condensed',
            'Saira Semi Condensed',
            'Salsa',
            'Sanchez',
            'Sancreek',
            'Sansita',
            'Sarala',
            'Sarina',
            'Sarpanch',
            'Satisfy',
            'Sawarabi Gothic',
            'Sawarabi Mincho',
            'Scada',
            'Scheherazade',
            'Schoolbell',
            'Scope One',
            'Seaweed Script',
            'Secular One',
            'Sedgwick Ave',
            'Sedgwick Ave Display',
            'Sevillana',
            'Seymour One',
            'Shadows Into Light',
            'Shadows Into Light Two',
            'Shanti',
            'Share',
            'Share Tech',
            'Share Tech Mono',
            'Shojumaru',
            'Short Stack',
            'Shrikhand',
            'Siemreap',
            'Sigmar One',
            'Signika',
            'Signika Negative',
            'Simonetta',
            'Sintony',
            'Sirin Stencil',
            'Six Caps',
            'Skranji',
            'Slabo 13px',
            'Slabo 27px',
            'Slackey',
            'Smokum',
            'Smythe',
            'Sniglet',
            'Snippet',
            'Snowburst One',
            'Sofadi One',
            'Sofia',
            'Song Myung',
            'Sonsie One',
            'Sorts Mill Goudy',
            'Source Code Pro',
            'Source Sans Pro',
            'Source Serif Pro',
            'Space Mono',
            'Special Elite',
            'Spectral',
            'Spectral SC',
            'Spicy Rice',
            'Spinnaker',
            'Spirax',
            'Squada One',
            'Sree Krushnadevaraya',
            'Sriracha',
            'Srisakdi',
            'Stalemate',
            'Stalinist One',
            'Stardos Stencil',
            'Stint Ultra Condensed',
            'Stint Ultra Expanded',
            'Stoke',
            'Strait',
            'Stylish',
            'Sue Ellen Francisco',
            'Suez One',
            'Sumana',
            'Sunflower',
            'Sunshiney',
            'Supermercado One',
            'Sura',
            'Suranna',
            'Suravaram',
            'Suwannaphum',
            'Swanky and Moo Moo',
            'Syncopate',
            'Tajawal',
            'Tangerine',
            'Taprom',
            'Tauri',
            'Taviraj',
            'Teko',
            'Telex',
            'Tenali Ramakrishna',
            'Tenor Sans',
            'Text Me One',
            'The Girl Next Door',
            'Tienne',
            'Tillana',
            'Timmana',
            'Tinos',
            'Titan One',
            'Titillium Web',
            'Trade Winds',
            'Trirong',
            'Trocchi',
            'Trochut',
            'Trykker',
            'Tulpen One',
            'Ubuntu',
            'Ubuntu Condensed',
            'Ubuntu Mono',
            'Ultra',
            'Uncial Antiqua',
            'Underdog',
            'Unica One',
            'UnifrakturCook',
            'UnifrakturMaguntia',
            'Unkempt',
            'Unlock',
            'Unna',
            'VT323',
            'Vampiro One',
            'Varela',
            'Varela Round',
            'Vast Shadow',
            'Vesper Libre',
            'Vibur',
            'Vidaloka',
            'Viga',
            'Voces',
            'Volkhov',
            'Vollkorn',
            'Vollkorn SC',
            'Voltaire',
            'Waiting for the Sunrise',
            'Wallpoet',
            'Walter Turncoat',
            'Warnes',
            'Wellfleet',
            'Wendy One',
            'Wire One',
            'Work Sans',
            'Yanone Kaffeesatz',
            'Yantramanav',
            'Yatra One',
            'Yellowtail',
            'Yeon Sung',
            'Yeseva One',
            'Yesteryear',
            'Yrsa',
            'Zeyada',
            'Zilla Slab',
            'Zilla Slab Highlight',
            );
        }

        /**
         * Get website current language
         *
         * @return void
         */
        function affiliatepress_get_site_current_language()
        {
            $affiliatepress_site_current_language = get_locale();
            
            if ($affiliatepress_site_current_language == 'ru_RU' ) {
                $affiliatepress_site_current_language = 'ru';
            } elseif ($affiliatepress_site_current_language == 'bs_BA' ) {
                $affiliatepress_site_current_language = 'ba';// bosnia
            } elseif ($affiliatepress_site_current_language == 'vi' ) {
                $affiliatepress_site_current_language = 'vn';// Vietnamese
            } elseif ($affiliatepress_site_current_language == 'sw' ) {
                $affiliatepress_site_current_language = 'se';// Swedish
            } elseif ($affiliatepress_site_current_language == 'ar' ) {
                $affiliatepress_site_current_language = 'ar'; // arabic
            } elseif ($affiliatepress_site_current_language == 'bg_BG' ) {
                $affiliatepress_site_current_language = 'bg'; // Bulgeria
            } elseif ($affiliatepress_site_current_language == 'ca' ) {
                $affiliatepress_site_current_language = 'ca'; // Canada
            } elseif ($affiliatepress_site_current_language == 'da_DK' ) {
                $affiliatepress_site_current_language = 'da'; // Denmark
            } elseif ($affiliatepress_site_current_language == 'de_DE' || $affiliatepress_site_current_language == 'de_CH_informal' || $affiliatepress_site_current_language == 'de_AT' || $affiliatepress_site_current_language == 'de_CH' || $affiliatepress_site_current_language == 'de_DE_formal' ) {
                $affiliatepress_site_current_language = 'de'; // Germany
            } elseif ($affiliatepress_site_current_language == 'el' ) {
                $affiliatepress_site_current_language = 'el'; // Greece
            } elseif ($affiliatepress_site_current_language == 'es_ES' ) {
                $affiliatepress_site_current_language = 'es'; // Spain
            } elseif ($affiliatepress_site_current_language == 'fr_FR' ) {
                $affiliatepress_site_current_language = 'fr'; // France
            } elseif ($affiliatepress_site_current_language == 'hr' ) {
                $affiliatepress_site_current_language = 'hr'; // Croatia
            } elseif ($affiliatepress_site_current_language == 'hu_HU' ) {
                $affiliatepress_site_current_language = 'hu'; // Hungary
            } elseif ($affiliatepress_site_current_language == 'id_ID' ) {
                $affiliatepress_site_current_language = 'id'; // Indonesia
            } elseif ($affiliatepress_site_current_language == 'is_IS' ) {
                $affiliatepress_site_current_language = 'is'; // Iceland
            } elseif ($affiliatepress_site_current_language == 'it_IT' ) {
                $affiliatepress_site_current_language = 'it'; // Italy
            } elseif ($affiliatepress_site_current_language == 'ja' ) {
                $affiliatepress_site_current_language = 'ja'; // Japan
            } elseif ($affiliatepress_site_current_language == 'ko_KR' ) {
                $affiliatepress_site_current_language = 'ko'; // Korean
            } elseif ($affiliatepress_site_current_language == 'lt_LT' ) {
                $affiliatepress_site_current_language = 'lt'; // Lithunian
            } elseif ($affiliatepress_site_current_language == 'mn' ) {
                $affiliatepress_site_current_language = 'mn'; // Mongolia
            } elseif ($affiliatepress_site_current_language == 'nl_NL' ) {
                $affiliatepress_site_current_language = 'nl'; // Netherlands
            } elseif ($affiliatepress_site_current_language == 'pl_PL' ) {
                $affiliatepress_site_current_language = 'pl'; // Poland
            } elseif ($affiliatepress_site_current_language == 'pt_BR' ) {
                $affiliatepress_site_current_language = 'pt-br'; // Portuguese
            } elseif ($affiliatepress_site_current_language == 'ro_RO' ) {
                $affiliatepress_site_current_language = 'ro'; // Romania
            } elseif ($affiliatepress_site_current_language == 'sk_SK' ) {
                $affiliatepress_site_current_language = 'sk'; // Slovakia
            } elseif ($affiliatepress_site_current_language == 'sl_SI' ) {
                $affiliatepress_site_current_language = 'sl'; // Slovenia
            } elseif ($affiliatepress_site_current_language == 'sq' ) {
                $affiliatepress_site_current_language = 'sq'; // Albanian
            } elseif ($affiliatepress_site_current_language == 'sr_RS' ) {
                $affiliatepress_site_current_language = 'sr'; // Suriname
            } elseif ($affiliatepress_site_current_language == 'sv_SE' ) {
                $affiliatepress_site_current_language = 'sv'; // El Salvador
            } elseif ($affiliatepress_site_current_language == 'tr_TR' ) {
                $affiliatepress_site_current_language = 'tr'; // Turkey
            } elseif ($affiliatepress_site_current_language == 'uk' ) {
                $affiliatepress_site_current_language = 'uk'; // Ukrain
            } elseif ($affiliatepress_site_current_language == 'vi' ) {
                $affiliatepress_site_current_language = 'vi'; // Virgin Islands (U.S.)
            } elseif ($affiliatepress_site_current_language == 'zh_CN' ) {
                $affiliatepress_site_current_language = 'zh-cn'; // Chinese
            } elseif ($affiliatepress_site_current_language == 'ka_GE' ) {
                $affiliatepress_site_current_language = 'ka'; // Georgian
            } elseif ($affiliatepress_site_current_language == 'nl_BE'){
                $affiliatepress_site_current_language = 'nl-be';
            } elseif ($affiliatepress_site_current_language == 'cs_CZ'){
                $affiliatepress_site_current_language = 'cs';
            }elseif ($affiliatepress_site_current_language == 'pt_PT'){
                $affiliatepress_site_current_language = 'pt';
            } elseif ($affiliatepress_site_current_language == 'et'){
                $affiliatepress_site_current_language = 'et';
            }elseif ($affiliatepress_site_current_language == 'nb_NO'){
                $affiliatepress_site_current_language = 'no'; //Norwegian
            }elseif ($affiliatepress_site_current_language == 'lv'){
                $affiliatepress_site_current_language = 'lv'; //Latvian
            }elseif ($affiliatepress_site_current_language == 'az'){
                $affiliatepress_site_current_language = 'az'; //Azerbijani
            }elseif ($affiliatepress_site_current_language == 'fi'){
                $affiliatepress_site_current_language = 'fi'; //Finnish
            }elseif ($affiliatepress_site_current_language == 'gl_ES' ) {
                $affiliatepress_site_current_language = 'gl';
            }elseif ($affiliatepress_site_current_language == 'he_IL' ) {
                $affiliatepress_site_current_language = 'he';//Hebrew
            }else {
                $affiliatepress_site_current_language = 'en';
            }

            return $affiliatepress_site_current_language;
        }
    }
}
global $affiliatepress_global_options;
$affiliatepress_global_options        = new affiliatepress_global_options();