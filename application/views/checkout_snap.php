<html>
<title>Midtrans</title>

<head>
    <script type="text/javascript" src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="<?php echo $clientKey ?>"></script>
    <script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
</head>

<body>
    <form id="payment-form" method="post" action="<?php echo site_url('/snap/finish'); ?>">
        <input type="hidden" name="result_type" id="result-type" value=""></div>
        <input type="hidden" name="result_data" id="result-data" value=""></div>
    </form>
    <p style="margin: 0; position: absolute; top: 50%; left: 50%; -ms-transform: translate(-50%, -50%); transform: translate(-50%, -50%);"><img style="max-width: 250px;" src="<?php echo base_url('assets/img/logo.png') ?>" id="img_loading"/>

        <!-- <button id="pay-button">Testing Midtrans</button> -->
        <script type="text/javascript">
            $(document).ready(function() {
                // event.preventDefault();
                // $(this).attr("disabled", "disabled");
                var url = "<?php echo site_url('/snap/token/' . $id_order); ?>";
                console.log(url);
                $.ajax({
                    url: url,
                    cache: false,

                    success: function(data) {
                        //location = data;

                        console.log('token = ' + data);
                        window.location.replace("https://app.sandbox.veritrans.co.id/snap/v2/vtweb/" + data);

                        var resultType = document.getElementById('result-type');
                        var resultData = document.getElementById('result-data');

                        function changeResult(type, data) {
                            $("#result-type").val(type);
                            $("#result-data").val(JSON.stringify(data));
                            //resultType.innerHTML = type;
                            //resultData.innerHTML = JSON.stringify(data);
                        }

                        snap.pay(data, {

                            onSuccess: function(result) {
                                changeResult('success', result);
                                console.log(result.status_message);
                                console.log(result);
                                $("#payment-form").submit();
                            },
                            onPending: function(result) {
                                changeResult('pending', result);
                                console.log(result.status_message);
                                $("#payment-form").submit();
                            },
                            onError: function(result) {
                                changeResult('error', result);
                                console.log(result.status_message);
                                $("#payment-form").submit();
                            }
                        });
                    }
                });
            });
        </script>


</body>

</html>