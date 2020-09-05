/**
 * Created by sahmar on 8/18/18.
 */

var Survey = {
    data: [],
    types: {
        short_text: {
            title: surveyLang.ShortText,
            type: 'short_text',
            property: false,
        },
        long_text: {
            title: surveyLang.LongText,
            type: 'long_text',
            property: false,
        },
        choice: {
            title:surveyLang.Choice,
            type: 'choice',
            property: {
                title: 'Option',
                icon: 'la-check-circle',
            },
        },
        multi_choice: {
            title: surveyLang.MultiChoice,
            type: 'multi_choice',
            property: {
                title: 'Option',
                icon: 'la-check-square',
            },
        },
        dropdown: {
            title: surveyLang.Dropdown,
            type: 'dropdown',
            property: {
                title: 'Option',
                icon: 'la-list',
            },
        },
        fileupload: {
            title: surveyLang.FileUpload,
            type: 'fileupload',
            property: false,
        },
        property: {
            title: surveyLang.Date,
            type: 'date',
            property: false,
        },
        time: {
            title: surveyLang.Time,
            type: 'time',
            property: false,
        },
        interval: {
            title: surveyLang.Interval,
            type: 'interval',
            property: false,
        },
    },

    defaultType: 'choice',

    init: function(){
        var jsonData = JSON.parse(document.getElementById("survey_json").value);
        if(jsonData)
            Survey.data = jsonData;
        $(".query_add").on('click', function() {
            Survey.addQuestion();
        });
        Survey.registerHandlers();
        Survey.render();
    },

    registerHandlers: function(){
        $(".query_select").change(function() {
            var qIndex = ($(this).attr('qIndex'));
            Survey.propertyChanged(qIndex, this.value);
        });
        $(".query_title").on('keyup', function() {
            var qIndex = ($(this).attr('qIndex'));
            Survey.titleChanged(qIndex, this.value);
        });
        $(".query_prop_title").on('keyup', function() {
            var qIndex = ($(this).attr('qIndex'));
            var pIndex = ($(this).attr('pIndex'));
            Survey.propertyTitleChanged(qIndex, pIndex, this.value);
        });
        $(".query_prop_add").on('click', function() {
            var qIndex = ($(this).attr('qIndex'));
            Survey.addProperty(qIndex);
        });
        $(".survey_close").on('click', function() {
            var qIndex = ($(this).attr('qIndex'));
            Survey.deleteQuery(qIndex);
        });
        $(".survey_ac_another").on('click', function() {
            var qIndex = ($(this).attr('qIndex'));
            Survey.toggleOtherChoice(qIndex);
        });
        $(".survey_ac_required").on('click', function() {
            var qIndex = ($(this).attr('qIndex'));
            Survey.toggleRequired(qIndex);
        });
        $(".query_prop_delete").on('click', function() {
            var qIndex = ($(this).attr('qIndex'));
            var pIndex = ($(this).attr('pIndex'));
            Survey.deleteProperty(qIndex, pIndex);
        });
    },

    render: function(){
        Survey.setJson();
        var html = '';
        for(var qIndex in Survey.data){
            var qData = Survey.data[qIndex];
            html += Survey.getQuestionTemplate(qIndex, qData);
        }
        $("#questions_body").html(html)
        Survey.registerHandlers();
    },

    setJson: function(){
        document.getElementById("survey_json").value = JSON.stringify(Survey.data);
    },

    addQuestion: function(){
        var qId = Survey.uniqueID();
        Survey.data[qId] = {
            //id: Survey.uniqueID(),
            title: "",
            type: "short_text",
            properties: {},
            isRequired: true,
            hasAnother: true,
        }
        Survey.render();
    },

    addProperty: function(qIndex){
        Survey.data[qIndex].properties[Survey.uniqueID()] = {title: ''};
        Survey.render();
    },

    deleteProperty: function(qIndex, pIndex){
        var qData = Survey.data[qIndex];
        delete qData.properties[pIndex];
        if(Object.keys(qData.properties).length == 0){
            Survey.addProperty(qIndex);
            return;
        }
        Survey.render();
    },

    deleteQuery: function(qIndex){
        delete Survey.data[qIndex];
        //Survey.data.splice(qIndex, 1);
        Survey.render();
    },

    getQuestionTemplate: function(qIndex, data){
        var html = '<div class="qb_line">'
            +'<div class="qb_first_line">'
                //+'<div class="col-8">'
            +'<input class="form-control m-input query_title" qIndex="'+qIndex+'" type="text" value="'+data.title+'" placeholder="'+surveyLang.Title+'" autocomplete="off"/>'
                //+'</div>'
            +'<div class="query_prop_seperator"></div>'
            +'<div class="query_render_types">'
            + Survey.renderTypes(qIndex, data)
            +'</div>'
            +'<div class="query_prop_seperator"></div>'
            + Survey.renderCloseButton(qIndex)
            +'</div>'
            + Survey.renderProperties(qIndex, data)
            + Survey.renderAnotherChoiceBtn(qIndex, data)
            +'</div>';
        html = html.replace(new RegExp('{number}', 'g'), qIndex);
        //if(qIndex/2 !== parseInt(qIndex/2)) html = '<div style="background: #f9f9f9">'+html+'</div>';
        return html;
    },

    renderCloseButton: function(qIndex){
        var html = '<div class="survey_close" qIndex="'+qIndex+'"><i class="la la-times-circle"></i></div>';
        return html;
    },

    renderAnotherChoiceBtn: function(qIndex, data){
        var html = '';
        html += '<div class="survey_ac_line">';
        if(data.type == "choice" || data.type == "multi_choice" || data.type == "dropdown"){
            html += '<div class="survey_ac_btn survey_ac_another" qIndex="'+qIndex+'">';
            html += '<div class="survey_ac_btn_icon"><i class="la '+((data.hasAnother) ? 'la-check-square': 'la-square')+'"></i></div>';
            html += '<div class="survey_ac_btn_text">'+surveyLang.AllowAddAlternative+'</div>';
            html += '</div>';
            html += '<div class="query_prop_seperator"></div>';
        }
        html += '<div class="survey_ac_btn survey_ac_required" qIndex="'+qIndex+'">';
        html += '<div class="survey_ac_btn_icon"><i class="la '+((data.isRequired) ? 'la-check-square': 'la-square')+'"></i></div>';
        html += '<div class="survey_ac_btn_text">'+surveyLang.IsRequired+'</div>';
        html += '</div>';
        html += '</div>';
        return html;
    },

    renderTypes: function(qIndex, data){
        var html = '';
        html += '<select class="form-control m-input query_select" name="type['+qIndex+']" qIndex="'+qIndex+'">';
        for (var qKey in Survey.types){
            var qType = Survey.types[qKey];
            html += '<option value="'+qType.type+'" '+(qType.type == data.type ? 'selected="selected"': '')+'>'
                + Survey.htmlEncode(qType.title)
                + '</option>';
        }
        html += '</select>';
        return html;
    },

    renderProperties: function(qIndex, data){
        //if(data.properties.length == 0 && data.type == "choice") return '';
        var html = '';
        if(data.type == "multi_choice" || data.type == "choice" || data.type == "dropdown"){
            var iii=0;
            html += '<div class="query_props_body">';
            var propsCount = Object.keys(data.properties).length;
            for(var pIndex in data.properties){
                var pData = data.properties[pIndex];
                html += '<div class="query_prop_line">';
                //if(data.type == "choice" || data.type == "multi_choice" || data.type == "dropdown"){
                html += '<div class="query_prop_items">';

                if(propsCount >= 0){
                    html += '<div class="query_prop_btn "><i class="la '+(Survey.types[data.type].property.icon)+'"></i></div>';
                    html += '<div class="query_prop_seperator"></div>';
                }

                html += '<input class="form-control m-input query_prop_title" qIndex="'+qIndex+'" pIndex="'+pIndex+'" type="text" value="'+Survey.htmlEncode(pData.title)+'" placeholder="'+surveyLang.Option+'" autocomplete="off"/>';

                if(propsCount >= 0){
                    html += '<div class="query_prop_seperator"></div>';
                    html += '<div class="query_prop_btn query_prop_delete" qIndex="'+qIndex+'" pIndex="'+pIndex+'"><i class="la la-trash"></i></div>';
                }

                if(iii == propsCount - 1){
                    html += '<div class="query_prop_seperator"></div>';
                    html += '<div class="query_prop_btn query_prop_add" qIndex="'+qIndex+'" pIndex="'+pIndex+'"><i class="la la-plus"></i></div>';
                }

                html += '</div>';
                //}
                html += '</div>';
                iii++;
            }
            html += '</div>';
        }else if(data.type == "interval"){
            html += '<div class="query_props_body">';
            html += '<div class="interval_line">';
            html += '<div class="interval_min">';
            html += '<input class="form-control m-input query_prop_title" qIndex="'+qIndex+'" pIndex="value_min" type="text" value="'+((data.value_min) ? Survey.htmlEncode(data.value_min): "")+'" placeholder="'+surveyLang.Minimum+'" autocomplete="off"/>';
            html += '</div>';

            html += '<div class="query_prop_seperator"></div>';

            html += '<div class="interval_min">';
            html += '<input class="form-control m-input query_prop_title" qIndex="'+qIndex+'" pIndex="value_max" type="text" value="'+((data.value_max) ? Survey.htmlEncode(data.value_max): "")+'" placeholder="'+surveyLang.Maximum+'" autocomplete="off"/>';
            html += '</div>';
            html += '</div>';
            html += '</div>';
        }

        return html;
    },

    propertyChanged: function(qIndex, value){
        Survey.data[qIndex].type = value;
        if(value == "choice" || value == "multi_choice" || value == "dropdown"){
            Survey.data[qIndex].properties = {}
            Survey.addProperty(qIndex);
        }else{
            Survey.render();
        }
    },

    toggleOtherChoice: function(qIndex){
        var qData = Survey.data[qIndex];
        qData.hasAnother = (qData.hasAnother) ? false: true;
        Survey.render();
    },

    toggleRequired: function(qIndex){
        var qData = Survey.data[qIndex];
        qData.isRequired = (qData.isRequired) ? false: true;
        Survey.render();
    },

    titleChanged: function(qIndex, value){
        Survey.data[qIndex].title = value;
        Survey.setJson();
    },

    propertyTitleChanged: function(qIndex, pIndex, value){
        if(pIndex == "value_min"){
            Survey.data[qIndex].value_min = parseInt(value);
        }else if(pIndex == "value_max"){
            Survey.data[qIndex].value_max = parseInt(value);
        }else{
            Survey.data[qIndex].properties[pIndex].title = value;
        }
        Survey.setJson();
    },


    uniqueID: function(){
        return Survey.chr4() + Survey.chr4() +
            '-' + Survey.chr4() +
            '-' + Survey.chr4() +
            '-' + Survey.chr4() +
            '-' + Survey.chr4() + Survey.chr4() + Survey.chr4();
    },

    chr4: function (){
        return Math.random().toString(16).slice(-4);
    },

    htmlEncode: function(str){
        if(str.length > 0)
            return str.replace(/&/g, "&amp;").replace(/>/g, "&gt;").replace(/</g, "&lt;").replace(/"/g, "&quot;");
        return str;
    }
}