/**
 * Created by fuzuc on 2016-08-23.
 *
 * <script src="http://oss.web/alioss/alioss.js"></script>
 * alioss.set_policy_param(data);
 *
 *
 */

var ALI_OSS = {
    expire:0
};
var alioss = {
    ossUpload:AliyunOSSEnabled,
    policyUrl:AliyunOSSPolicyUrl,
    random_string : function (len) {
        len = len || 32;
        var chars = 'ABCDEFGHJKMNPQRSTWXYZabcdefhijkmnprstwxyz2345678';
        var maxPos = chars.length;
        var pwd = '';
        for (var i = 0; i < len; i++) {
            pwd += chars.charAt(Math.floor(Math.random() * maxPos));
        }
        return pwd;
    },
     get_suffix:function(filename) {
        var pos = filename.lastIndexOf('.');
        if (pos != -1) {
            this.suffix = filename.substring(pos)
        }
        return this.suffix;
    },
    set_policy_param:function (data) {
        if (this.ossUpload){
            var now = Date.parse(new Date()) / 1000;
            if (ALI_OSS.expire < now + 3 ){
                $.ajax({
                    url     : this.policyUrl,
                    type    : 'post',
                    dataType: 'json',
                    async   : false,
                    success : function (result)
                    {
                        ALI_OSS.dir               = result.dir ;
                        ALI_OSS.policy            = result.policy;
                        ALI_OSS.OSSAccessKeyId    = result.accessid;
                        ALI_OSS.success_action_status  = 200;
                        ALI_OSS.callback          = result.callback;
                        ALI_OSS.signature         = result.signature;
                        ALI_OSS.url               = result.host;
                        ALI_OSS.expire            = result.expire;
                    }
                });
            }
            data.formData.key = ALI_OSS.dir + this.random_string(16).toLowerCase()+ this.get_suffix(data.originalFiles[0].name);
            data.formData.policy            = ALI_OSS.policy;
            data.formData.OSSAccessKeyId    = ALI_OSS.OSSAccessKeyId;
            data.formData.success_action_status  = ALI_OSS.success_action_status;
            data.formData.callback          = ALI_OSS.callback;
            data.formData.signature         = ALI_OSS.signature;
            data.url            = ALI_OSS.url;
            data.paramName      = 'file';
            data.xhrFields      = {withCredentials:false};
        }
    },
    set_upload_done_param:function (data) {
        if (this.ossUpload) {
            var result = data['result']['info'][0];
            data['result'][0] = [];
            data['result'][0]['url'] = result.url;
            data['result'][0]['delete_hash'] = result.delete_hash;
            data['result'][0]['short_url'] = result.short_url;
            data['result'][0]['error'] = result.error;
            data['result'][0]['success_result_html'] = result.success_result_html;
            data['result'][0]['error_result_html'] = result.error_result_html;
        }
    }
};
