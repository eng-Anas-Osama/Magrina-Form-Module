<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class CustomCheckout extends Module
{
    public function __construct()
    {
        $this->name = 'customcheckout';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'Zastoor';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = [
            'min' => '8.1.0',
            'max' => _PS_VERSION_
        ];
        $this->bootstrap = true;
        if (Tools::getValue('controller') == 'order') {
            $this->context->controller->registerJavascript(
                'custom-address-form-js',
                'modules/'.$this->name.'/views/js/custom-address-form.js',
                ['position' => 'bottom', 'priority' => 150]
            );
        }

        parent::__construct();

        $this->displayName = $this->l('Custom Checkout');
        $this->description = $this->l('Customizes the checkout process for guest users with Egyptian-specific fields.');

        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');
    }

    public function install()
    {
        if (!parent::install() ||
            !$this->registerHook('displayCustomerAddressForm') ||
            !$this->registerHook('actionValidateCustomerAddressForm') ||
            !$this->registerHook('actionSubmitCustomerAddressForm') ||
            // !$this->registerHook('displayCarrierExtraContent') ||
            !$this->registerHook('actionCarrierProcess') ||
            !$this->executeSqlFile('install.sql') ||
            !$this->populateCustomTables() ||
            !$this->createCustomCarrier() ||
            !$this->setupShippingPrices()) {
            return false;
        }

        return true;
    }
    public function uninstall()
    {
        return parent::uninstall();
    }

    private function executeSqlFile($file)
    {
        if (!file_exists(dirname(__FILE__) . '/' . $file)) {
            return false;
        }
        $sql = Tools::file_get_contents(dirname(__FILE__) . '/' . $file);
        $sql = str_replace('PREFIX_', _DB_PREFIX_, $sql);
        $sql = preg_split("/;\s*[\r\n]+/", $sql);

        foreach ($sql as $query) {
            if (!empty(trim($query))) {
                if (!Db::getInstance()->execute(trim($query))) {
                    return false;
                }
            }
        }
        return true;
    }

    private function populateCustomTables()
    {
        $governments_and_states = [
            'Cairo' => ['Nasr City', 'Maadi', 'Heliopolis', 'Downtown', 'Zamalek'],
            'Alexandria' => ['Montazah', 'Sidi Gaber', 'Mansheya', 'Miami', 'Agami'],
            'Giza' => ['Dokki', 'Mohandessin', '6th of October City', 'Sheikh Zayed City'],
            'Shubra El Kheima' => ['Shubra', 'El Khema', 'Bahtim'],
            'Port Said' => ['El Sharq', 'El Arab', 'El Manakh', 'El Zohour'],
            'Suez' => ['Arbaeen', 'Suez', 'Attaka', 'El Ganayen'],
            'Luxor' => ['Luxor City', 'Karnak', 'New Thebes', 'El Toud'],
            'Aswan' => ['Aswan City', 'Edfu', 'Kom Ombo', 'Daraw'],
            'Asyut' => ['Asyut City', 'Dairut', 'Manfalut', 'Abnoub'],
            'Ismailia' => ['Ismailia City', 'El Tal El Kabier', 'Fayed', 'El Qantara'],
            'Fayoum' => ['Fayoum City', 'Sinnuris', 'Tamiya', 'Youssef El Seddik'],
            'Zagazig' => ['Zagazig City', 'Belbeis', 'Minya El Qamh', 'Hehya'],
            'Tanta' => ['Tanta City', 'El Mahalla El Kubra', 'Kafr El Zayat', 'Zifta'],
            'Sohag' => ['Sohag City', 'Akhmim', 'Tama', 'El Maragha']
        ];

        foreach ($governments_and_states as $government => $states) {
            // Insert government
            Db::getInstance()->insert('custom_government', [
                'name' => pSQL($government)
            ]);
            $governmentId = Db::getInstance()->Insert_ID();

            // Insert states for this government
            foreach ($states as $state) {
                Db::getInstance()->insert('custom_state', [
                    'id_government' => (int)$governmentId,
                    'name' => pSQL($state)
                ]);
            }
        }

        // Create zones
        $zones = [
            'Zone A' => ['Cairo', 'Giza', 'Alexandria'],
            'Zone B' => ['Port Said', 'Suez', 'Ismailia'],
            'Zone C' => ['Luxor', 'Aswan', 'Asyut', 'Sohag'],
            'Zone D' => ['Fayoum', 'Zagazig', 'Tanta', 'Shubra El Kheima']
        ];

        foreach ($zones as $zoneName => $zoneGovernments) {
            // Insert zone
            Db::getInstance()->insert('custom_zone', [
                'name' => pSQL($zoneName)
            ]);
            $zoneId = Db::getInstance()->Insert_ID();

            // Assign governments to zone
            foreach ($zoneGovernments as $governmentName) {
                $governmentId = Db::getInstance()->getValue('
                SELECT id_government 
                FROM '._DB_PREFIX_.'custom_government 
                WHERE name = "'.pSQL($governmentName).'"
            ');

                if ($governmentId) {
                    Db::getInstance()->insert('custom_zone_government', [
                        'id_zone' => (int)$zoneId,
                        'id_government' => (int)$governmentId
                    ]);
                }
            }
        }

        return true;
    }

    public function hookDisplayCustomerAddressForm($params)
    {
        $form = $params['form'];

        // Get governments from your custom table
        $governments = $this->getGovernments();

        $this->context->smarty->assign([
            'action' => $this->context->link->getPageLink('order'),
            'governments' => $governments,
            'full_name' => $form->getField('full_name')->getValue(),
            'phone' => $form->getField('phone')->getValue(),
            'government' => $form->getField('government')->getValue(),
            'state' => $form->getField('state')->getValue(),
            'address' => $form->getField('address')->getValue(),
            'notes' => $form->getField('notes')->getValue(),
            'email' => $form->getField('email')->getValue(),
        ]);

        return $this->display(__FILE__, 'views/templates/hook/custom_checkout_form.tpl');

        // // Custom form fields implementation
        // $form = $params['form'];

        // // Remove default fields we don't need
        // $form->remove('firstname');
        // $form->remove('lastname');
        // $form->remove('company');
        // $form->remove('vat_number');
        // $form->remove('address1');
        // $form->remove('address2');
        // $form->remove('postcode');
        // $form->remove('city');
        // $form->remove('phone');
        // $form->remove('phone_mobile');

        // // Add full name field
        // $form->add('text', 'full_name', [
        //     'label' => $this->l('Full Name'),
        //     'required' => true,
        //     'constraints' => [
        //         new \Symfony\Component\Validator\Constraints\NotBlank([
        //             'message' => $this->l('Please enter your full name.')
        //         ])
        //     ]
        // ]);

        // // Add phone number field with Egyptian validation
        // $form->add('text', 'phone', [
        //     'label' => $this->l('Phone Number'),
        //     'required' => true,
        //     'constraints' => [
        //         new \Symfony\Component\Validator\Constraints\NotBlank([
        //             'message' => $this->l('Please enter your phone number.')
        //         ]),
        //         new \Symfony\Component\Validator\Constraints\Regex([
        //             'pattern' => '/^01[0-2,5]{1}[0-9]{8}$/',
        //             'message' => $this->l('Please enter a valid Egyptian phone number (11 digits starting with 01).')
        //         ])
        //     ]
        // ]);

        // // Add government field
        // $form->add('select', 'government', [
        //     'label' => $this->l('Government'),
        //     'required' => true,
        //     'choices' => $this->getEgyptianGovernments(),
        //     'constraints' => [
        //         new \Symfony\Component\Validator\Constraints\NotBlank([
        //             'message' => $this->l('Please select a government.')
        //         ])
        //     ]
        // ]);

        // // Add state field
        // $form->add('select', 'state', [
        //     'label' => $this->l('State'),
        //     'required' => true,
        //     'choices' => [],
        //     'constraints' => [
        //         new \Symfony\Component\Validator\Constraints\NotBlank([
        //             'message' => $this->l('Please select a state.')
        //         ])
        //     ]
        // ]);

        // // Add address field
        // $form->add('textarea', 'address', [
        //     'label' => $this->l('Address'),
        //     'required' => true,
        //     'constraints' => [
        //         new \Symfony\Component\Validator\Constraints\NotBlank([
        //             'message' => $this->l('Please enter your address.')
        //         ])
        //     ]
        // ]);

        // // Add notes field
        // $form->add('textarea', 'notes', [
        //     'label' => $this->l('Notes (Optional)'),
        //     'required' => false,
        // ]);

        // // Add a field for account creation option
        // $form->add('checkbox', 'create_account', [
        //     'label' => $this->l('Create an account'),
        //     'required' => false,
        // ]);

        // // Add email field (initially hidden, shown when create_account is checked)
        // $form->add('email', 'email', [
        //     'label' => $this->l('Email'),
        //     'required' => false,
        //     'constraints' => [
        //         new \Symfony\Component\Validator\Constraints\Email([
        //             'message' => $this->l('Please enter a valid email address.')
        //         ])
        //     ]
        // ]);
        // $this->context->controller->addJS($this->_path.'views/js/custom-address-form.js');
        // return $form->getForm()->createView();
    }
    private function getGovernments()
    {
        $governments = Db::getInstance()->executeS('
        SELECT id_government, name 
        FROM '._DB_PREFIX_.'custom_government 
        ORDER BY name ASC
    ');

        $formattedGovernments = [];
        foreach ($governments as $government) {
            $formattedGovernments[$government['id_government']] = $government['name'];
        }

        return $formattedGovernments;
    }
    // private function getEgyptianGovernments()
    // {
    //     $governments = Db::getInstance()->executeS('
    //         SELECT id_government as id, name
    //         FROM '._DB_PREFIX_.'custom_government
    //         ORDER BY name ASC
    //     ');

    //     $formattedGovernments = [];
    //     foreach ($governments as $government) {
    //         $formattedGovernments[$government['id']] = $this->l($government['name']);
    //     }

    //     return $formattedGovernments;
    // }

    public function hookActionValidateCustomerAddressForm($params)
    {
        // Form validation logic
        $form = $params['form'];

        // Validate full name
        $fullName = $form->get('full_name')->getData();
        if (empty($fullName)) {
            $form->get('full_name')->addError(new FormError($this->l('Please enter your full name.')));
        }

        // Validate phone number
        $phone = $form->get('phone')->getData();
        if (!preg_match('/^01[0-2,5]{1}[0-9]{8}$/', $phone)) {
            $form->get('phone')->addError(new FormError($this->l('Please enter a valid Egyptian phone number (11 digits starting with 01).')));
        }

        // Validate government
        $government = $form->get('government')->getData();
        if (empty($government)) {
            $form->get('government')->addError(new FormError($this->l('Please select a government.')));
        }

        // Validate state
        $state = $form->get('state')->getData();
        if (empty($state)) {
            $form->get('state')->addError(new FormError($this->l('Please select a state.')));
        }

        // Validate address
        $address = $form->get('address')->getData();
        if (empty($address)) {
            $form->get('address')->addError(new FormError($this->l('Please enter your address.')));
        }

        // Validate email if account creation is checked
        $createAccount = $form->get('create_account')->getData();
        if ($createAccount) {
            $email = $form->get('email')->getData();
            if (empty($email)) {
                $form->get('email')->addError(new FormError($this->l('Please enter your email address.')));
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $form->get('email')->addError(new FormError($this->l('Please enter a valid email address.')));
            } else {
                // Check if email already exists
                $customer = new Customer();
                $customerExists = $customer->getByEmail($email);
                if ($customerExists) {
                    $form->get('email')->addError(new FormError($this->l('An account already exists with this email address. Please login or use a different email.')));
                }
            }
        }
    }

    // Form submission handling
    public function hookActionSubmitCustomerAddressForm($params)
    {
        $address = $params['address'];
        $form = $params['form'];

        // Save custom fields to the address object
        $address->alias = 'My Address'; // Set a default alias
        $address->firstname = ''; // We're not using these, but they're required
        $address->lastname = '';
        $address->address1 = $form->get('address')->getData();
        $address->city = $form->get('state')->getData(); // Using state as city
        $address->id_state = 0; // We're not using PrestaShop's default states
        $address->postcode = '00000'; // Set a default postcode
        $address->id_country = Country::getByIso('EG'); // Set country to Egypt

        // Save custom fields
        $address->other = json_encode([
            'full_name' => $form->get('full_name')->getData(),
            'phone' => $form->get('phone')->getData(),
            'government' => $form->get('government')->getData(),
            'state' => $form->get('state')->getData(),
            'notes' => $form->get('notes')->getData(),
        ]);

        // Save the address
        $address->save();

        // Handle account creation if checkbox is checked
        if ($form->get('create_account')->getData()) {
            $email = $form->get('email')->getData();
            $customer = new Customer();
            $customer->firstname = $form->get('full_name')->getData();
            $customer->lastname = '.';
            $customer->email = $email;
            $customer->passwd = Tools::encrypt(Tools::passwdGen());
            $customer->is_guest = 0;
            $customer->active = 1;
            $customer->add();

            // Associate the address with the new customer
            $address->id_customer = $customer->id;
            $address->update();

            // Log in the customer
            $this->context->updateCustomer($customer);
        }
    }
    public function getStatesByGovernment($governmentId)
    {
        $states = Db::getInstance()->executeS(
            '
        SELECT id_state as id, name 
        FROM '._DB_PREFIX_.'custom_state 
        WHERE id_government = '.(int)$governmentId.' 
        ORDER BY name ASC'
        );
        return $states;
    }

    // Creating Custom Carrier
    private function createCustomCarrier()
    {
        $carrier = new Carrier();
        $carrier->name = $this->l('Custom Egyptian Shipping');
        $carrier->is_module = true;
        $carrier->active = true;
        $carrier->range_behavior = 0;
        $carrier->need_range = true;
        $carrier->shipping_external = true;
        $carrier->external_module_name = $this->name;
        $carrier->shipping_method = Carrier::SHIPPING_METHOD_PRICE;

        foreach (Language::getLanguages() as $lang) {
            $carrier->delay[$lang['id_lang']] = $this->l('Delivery time depends on the shipping zone');
        }

        if ($carrier->add()) {
            // Associate the carrier with all zones
            $zones = Zone::getZones();
            foreach ($zones as $zone) {
                $carrier->addZone($zone['id_zone']);
            }

            // Add ranges and prices (example)
            $rangePrice = new RangePrice();
            $rangePrice->id_carrier = $carrier->id;
            $rangePrice->delimiter1 = '0';
            $rangePrice->delimiter2 = '10000';
            $rangePrice->add();

            // Add prices for each zone (example)
            foreach ($zones as $zone) {
                Db::getInstance()->insert('delivery', [
                    'id_carrier' => $carrier->id,
                    'id_range_price' => $rangePrice->id,
                    'id_range_weight' => null,
                    'id_zone' => $zone['id_zone'],
                    'price' => 10 // Default price, you might want to adjust this
                ]);
            }

            Configuration::updateValue('CUSTOM_CARRIER_ID', (int)$carrier->id);
            return true;
        }

        return false;
    }

    // public function hookDisplayCarrierExtraContent($params)
    // {
    //     // Display custom shipping options
    // }

    public function hookActionCarrierProcess($params)
    {
        $cart = $params['cart'];
        $deliveryAddress = new Address($cart->id_address_delivery);

        // Get the government from the custom fields
        $customFields = json_decode($deliveryAddress->other, true);
        $governmentId = $customFields['government'];

        // Get the zone for this government
        $zoneId = Db::getInstance()->getValue(
            '
        SELECT czg.id_zone 
        FROM '._DB_PREFIX_.'custom_zone_government czg
        WHERE czg.id_government = '.(int)$governmentId
        );

        if ($zoneId) {
            // Get the price for this zone
            $carrierId = Configuration::get('CUSTOM_CARRIER_ID');
            $price = $this->getShippingPriceForZone($zoneId);

            if ($price !== false) {
                // Update the shipping cost
                $cart->setPackageShippingCost($carrierId, $price);
            }
        }
    }

    private function getShippingPriceForZone($zoneId)
    {
        return Configuration::get('CUSTOM_SHIPPING_PRICE_ZONE_'.$zoneId);
    }

    private function setupShippingPrices()
    {
        $zonePrices = [
            'Zone A' => 50,
            'Zone B' => 75,
            'Zone C' => 100,
            'Zone D' => 125
        ];

        foreach ($zonePrices as $zoneName => $price) {
            $zoneId = Db::getInstance()->getValue('
            SELECT id_zone 
            FROM '._DB_PREFIX_.'custom_zone 
            WHERE name = "'.pSQL($zoneName).'"
        ');

            if ($zoneId) {
                Configuration::updateValue('CUSTOM_SHIPPING_PRICE_ZONE_'.$zoneId, $price);
            }
        }
    }

    private function getPrestaShopZoneId($customZoneId)
    {
        // This method should return the corresponding PrestaShop zone ID
        // You might want to create a mapping between your custom zones and PrestaShop zones
        // For simplicity, let's assume a direct mapping (custom zone 1 = PrestaShop zone 1, etc.)
        return $customZoneId;
    }
}
