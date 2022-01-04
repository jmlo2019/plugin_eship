<div class="container">
    <div class="mt-5 mb-5">
        <h2>Conexión con ESHIP</h2>
        <div class="row">
            <div class="col-12 col-sm-6 offset-sm-3">
                <div class="card">
                    <div class="card-body">
                        <?php if (isset($wc_img)) { ?>
                            <img src="<?php echo (isset($wc_img))? $wc_img : FALSE;?>" class="card-img-top" alt="...">
                        <?php } else { ?>
                            <h5 class="card-title">Woocommerce</h5>
                        <?php } ?>
                        <h5 class="card-title">Cuento con el token de ESHIP</h5>
                        <p class="card-text">Connect Woocommerce with E-ship</p>
                        <a href="#" class="btn btn-primary">Connect</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>