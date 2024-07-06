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

        parent::__construct();

        $this->displayName = $this->l('Custom Checkout');
        $this->description = $this->l('Customizes the checkout process for guest users with Egyptian-specific fields.');

        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');
    }

    public function install()
    {
        // return parent::install() &&
        //     $this->registerHook('displayCustomerAddressForm') &&
        //     $this->registerHook('actionValidateCustomerAddressForm') &&
        //     $this->registerHook('actionSubmitCustomerAddressForm') &&
        //     $this->registerHook('displayCarrierExtraContent') &&
        //     $this->registerHook('actionCarrierProcess');

        if (!parent::install() ||
        !$this->registerHook('displayCustomerAddressForm') ||
        !$this->registerHook('actionValidateCustomerAddressForm') ||
        !$this->registerHook('actionSubmitCustomerAddressForm')) {
            return false;
        }

        // Create custom tables
        if (!$this->executeSqlFile('install.sql')) {
            return false;
        }

        // Populate custom tables with data
        if (!$this->populateCustomTables()) {
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
        $governments = [
            'Cairo', 'Alexandria', 'Giza', 'Shubra El Kheima', 'Port Said', 'Suez', 'Luxor',
            'Aswan', 'Asyut', 'Ismailia', 'Fayoum', 'Zagazig', 'Assiut', 'Tanta', 'Sohag'
        ];

        foreach ($governments as $government) {
            Db::getInstance()->insert('custom_government', [
                'name' => pSQL($government)
            ]);
        }

        // For simplicity, we'll add some example states for Cairo
        $cairoId = Db::getInstance()->getValue('SELECT id_government FROM '._DB_PREFIX_.'custom_government WHERE name = "Cairo"');
        $cairoStates = ['Nasr City', 'Maadi', 'Heliopolis', 'Downtown', 'Zamalek'];

        foreach ($cairoStates as $state) {
            Db::getInstance()->insert('custom_state', [
                'id_government' => (int)$cairoId,
                'name' => pSQL($state)
            ]);
        }

        return true;
    }

    public function hookDisplayCustomerAddressForm($params)
    {
        // Custom form fields implementation
        $form = $params['form'];

        // Remove default fields we don't need
        $form->remove('firstname');
        $form->remove('lastname');
        $form->remove('company');
        $form->remove('vat_number');
        $form->remove('address1');
        $form->remove('address2');
        $form->remove('postcode');
        $form->remove('city');
        $form->remove('phone');
        $form->remove('phone_mobile');

        // Add full name field
        $form->add('text', 'full_name', [
            'label' => $this->l('Full Name'),
            'required' => true,
            'constraints' => [
                new \Symfony\Component\Validator\Constraints\NotBlank([
                    'message' => $this->l('Please enter your full name.')
                ])
            ]
        ]);

        // Add phone number field with Egyptian validation
        $form->add('text', 'phone', [
            'label' => $this->l('Phone Number'),
            'required' => true,
            'constraints' => [
                new \Symfony\Component\Validator\Constraints\NotBlank([
                    'message' => $this->l('Please enter your phone number.')
                ]),
                new \Symfony\Component\Validator\Constraints\Regex([
                    'pattern' => '/^01[0-2,5]{1}[0-9]{8}$/',
                    'message' => $this->l('Please enter a valid Egyptian phone number (11 digits starting with 01).')
                ])
            ]
        ]);

        // Add government field
        $form->add('select', 'government', [
            'label' => $this->l('Government'),
            'required' => true,
            'choices' => $this->getEgyptianGovernments(),
            'constraints' => [
                new \Symfony\Component\Validator\Constraints\NotBlank([
                    'message' => $this->l('Please select a government.')
                ])
            ]
        ]);

        // Add state field
        $form->add('select', 'state', [
            'label' => $this->l('State'),
            'required' => true,
            'choices' => [],
            'constraints' => [
                new \Symfony\Component\Validator\Constraints\NotBlank([
                    'message' => $this->l('Please select a state.')
                ])
            ]
        ]);

        // Add address field
        $form->add('textarea', 'address', [
            'label' => $this->l('Address'),
            'required' => true,
            'constraints' => [
                new \Symfony\Component\Validator\Constraints\NotBlank([
                    'message' => $this->l('Please enter your address.')
                ])
            ]
        ]);

        // Add notes field
        $form->add('textarea', 'notes', [
            'label' => $this->l('Notes (Optional)'),
            'required' => false,
        ]);

        // Add a field for account creation option
        $form->add('checkbox', 'create_account', [
            'label' => $this->l('Create an account'),
            'required' => false,
        ]);

        // Add email field (initially hidden, shown when create_account is checked)
        $form->add('email', 'email', [
            'label' => $this->l('Email'),
            'required' => false,
            'constraints' => [
                new \Symfony\Component\Validator\Constraints\Email([
                    'message' => $this->l('Please enter a valid email address.')
                ])
            ]
        ]);
        $this->context->controller->addJS($this->_path.'views/js/custom-address-form.js');
        return $form->getForm()->createView();
    }

    private function getEgyptianGovernments()
    {
        $governments = Db::getInstance()->executeS('
            SELECT id_government as id, name 
            FROM '._DB_PREFIX_.'custom_government 
            ORDER BY name ASC
        ');

        $formattedGovernments = [];
        foreach ($governments as $government) {
            $formattedGovernments[$government['id']] = $this->l($government['name']);
        }

        return $formattedGovernments;
    }

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
        WHERE id_government = '.(int)$governmentId
        );
        return $states;
    }
    public function hookDisplayCarrierExtraContent($params)
    {
        // Display custom shipping options
    }

    public function hookActionCarrierProcess($params)
    {
        // Process custom shipping logic
    }
}
