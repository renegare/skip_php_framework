<?php

    namespace Skip\Service;

    use Symfony\Component\Form\Forms;
    use Symfony\Component\Validator\Validation;
    use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
    use Symfony\Component\Form\Extension\Csrf\CsrfExtension;
    use Symfony\Component\Form\Extension\Csrf\CsrfProvider\DefaultCsrfProvider;
    use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
    use Symfony\Component\Form\FormEvent;
    use Symfony\Component\Form\FormEvents;
    // Annotate config validation

    class FormService implements \Skip\ServiceContainerInterface
    {
        protected $config;
        protected $app;
        protected $formFactory;

        public function __construct( $form_config, $csrf_provider_service )
        {
            FormService::validate( $form_config );

            $this->config = $form_config;

            // $csrfProvider = new DefaultCsrfProvider( $form_config['csrf_secret'] );

            // setup forms to use csrf and validator component
            $this->formFactory = Forms::createFormFactoryBuilder()
                ->addExtension( new CsrfExtension($csrf_provider_service) )
                ->addExtension( new ValidatorExtension( Validation::createValidator() ) )
                ->addExtension( new HttpFoundationExtension() )
                ->getFormFactory();
        }

        public function setContainer( \Pimple $app )
        {
            $this->app = $app;
        }

        public static function validate( $config )
        {
            if( !isset($config['schemas']) || !count($config['schemas']) )
            {
                throw new \Exception('You have no form schemas defined in FormService config');
            }
        }

        public function load( $key )
        {
            if( !isset($this->config['schemas'][$key]) )
            {
                throw new \Exception("Form not configured");
            }
            // @TODO: memcache?
            $schema = $this->config['schemas'][$key];
            $options = isset($schema['options'])? $schema['options'] : array();

            $name = isset($schema['name'])? $schema['name'] : '';

            $builder = $this->formFactory->createNamedBuilder( $name, 'form', null, $options );

            // @TODO: form config validation
            foreach( $schema['fields'] as $name => $options )
            {
                $field_options = isset($options['options'])? $options['options'] : array();

                if( isset($field_options['constraints']) )
                {
                    $constraints = $field_options['constraints'];
                    $constraints = array_map(function( $constraint ){

                        extract($constraint);

                        if( !class_exists($class) )
                        {
                            // assume its built into the form component ... fatal!!! 
                            $class_constraint = '\\Symfony\\Component\\Validator\\Constraints\\'.str_replace(' ', '', ucwords( str_replace('_',' ', $class) ) );
                        } else {
                            $class_constraint = $class;
                        }
                        if( isset( $options ) )
                        {
                            $constraint = new $class_constraint( $options );
                        } else {
                            $constraint = new $class_constraint();
                        }

                        return $constraint;

                    }, $constraints);

                    $field_options['constraints'] = $constraints;
                }


                $type = isset($options['type'])? $options['type'] : null;

                if( is_string( $type) && preg_match( "/^\@([A-Z0-9a-z_\\\]+)$/", $type, $match ) ) {

                    $typeClass = $match[1];

                    $type = new $typeClass();
                    if( $this->app && $type instanceof \Skip\ServiceContainerInterface ) {
                        $type->setContainer( $this->app );
                    }

                }

                $builder->add($name, $type , $field_options );
            }
            return $builder->getForm();
        }
    }