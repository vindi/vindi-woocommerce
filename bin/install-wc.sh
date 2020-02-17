WC_INSTALL_PATH=${WC_INSTALL_PATH-/tmp/woocommerce}
WC_VERSION=3.4.5



install_woocommerce() {
  if [ ! -d $WP_TESTS_DIR ]; then
    mkdir -p $WC_INSTALL_PATH

    cd $WC_INSTALL_PATH
    git clone https://github.com/woocommerce/woocommerce.git .
    git checkout $WC_VERSION
    cd -
  fi
}

install_woocommerce
