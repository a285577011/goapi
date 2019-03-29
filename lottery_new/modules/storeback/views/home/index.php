<style>
    #storeInfo lable{
        display: inline-block;
        width: 200px;
        text-align: right;
    }
</style>
<div style="border:2px solid #ccc;width: 30%;height:300px;margin: 20px auto;border-radius:15px;" id="storeInfo">
    <h2 style="margin-top:1.6rem;text-align: center;">欢迎！！</h2>
    <p>
        <lable>门店：</lable><span id="storeName"></span>(<span id="storeCode"></span>)
    </p>
     <p>
        <lable>门店地址：</lable><span id="province"></span> <span id="city"></span> <span id="area"></span>
    </p>
    <p>
        <lable>运营者：</lable><span id="cust_no"></span>
    </p>
</div>
<script type="text/javascript">
   $(function(){
       $.ajax({
            url: "/api/store/store/basic-info",
            type: "POST",
            data:{token:loaddata.get("token"),token_type:"storeBack"},
            async: false,
            dataType: "json",
            success:function(data){
//                console.log(data);
                if (data["code"] == 600) {
                    $("#storeName").html(data["result"]["store_name"]);
                    $("#storeCode").html(data["result"]["store_code"]);
                    $("#province").html(data["result"]["province"]);
                    $("#city").html(data["result"]["city"]);
                    $("#area").html(data["result"]["area"]);
//                    $("#address").html(data["result"]["address"]);
                    $("#cust_no").html(data["result"]["cust_no"]);
                }
            },
       })
   })
</script>
