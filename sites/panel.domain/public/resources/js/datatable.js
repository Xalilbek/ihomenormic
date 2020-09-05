var DatatableRecordSelectionDemo = function() {
    var t = {
        data: {
            type: "remote",
            source: {
                read: {
                    url: tableConf.listUrl
                }
            },
            pageSize: 10,
            serverPaging: !0,
            serverFiltering: !0,
            serverSorting: !0
        },
        layout: {
            theme: "default",
            class: "",
            scroll: !0,
            height: 550,
            footer: !1
        },
        sortable: !0,
        pagination: !0,
        columns: tableConf.columns
    };
    return {
        init: function() {
            ! function() {
                t.search = {
                    input: $("#generalSearch")
                };
                var e = $("#local_record_selection").mDatatable(t);
                $("#m_form_status").on("change", function()
                {
                    e.search($(this).val().toLowerCase(), "Status")
                }),
                    $(".search-filter").on("change", function()
                    {
                        e.search($(this).val(), $(this).attr("name"))
                    }),
                    $(".search-filter").on("keyup", function()
                    {
                        e.search($(this).val(), $(this).attr("name"))
                    }),

                    $(".search-checkbox").on("change", function()
                    {
                        e.search(($(this).is(':checked')) ? '1': '0', $(this).attr("name"))
                    }),

                    //$("#m_form_status,#m_form_type").selectpicker(),
                    $(".m-bootstrap-select").selectpicker(),

                    e.on("m-datatable--on-check m-datatable--on-uncheck m-datatable--on-layout-updated", function(t)
                    {
                        var a = e.rows(".m-datatable__row--active").nodes().length;
                        $("#m_datatable_selected_number").html(a), a > 0 ? $("#m_datatable_group_action_form").collapse("show") : $("#m_datatable_group_action_form").collapse("hide")
                    }),

                    $("#m_modal_fetch_id").on("show.bs.modal", function(t)
                    {
                        for (var a = e.rows(".m-datatable__row--active").nodes().find('.m-checkbox--single > [type="checkbox"]').map(function(t, e) {
                                return $(e).val()
                            }),
                                 n = document.createDocumentFragment(), l = 0; l < a.length; l++) {
                            var i = document.createElement("li");
                            i.setAttribute("data-id", a[l]), i.innerHTML = "Selected record ID: " + a[l], n.appendChild(i)
                        }
                        $(t.target).find(".m_datatable_selected_ids").append(n)
                    }).on("hide.bs.modal", function(t) {
                        $(t.target).find(".m_datatable_selected_ids").empty()
                    })

                    $("#data_execute").on("click", function(t)
                    {
                        for (var a = e.rows(".m-datatable__row--active").nodes().find('.m-checkbox--single > [type="checkbox"]').map(function(t, e) {
                                return $(e).val()
                            }),
                           ids = [],

                            n = document.createDocumentFragment(), l = 0; l < a.length; l++) {
                            var i = document.createElement("li");
                            ids[ids.length] = a[l];
                            i.setAttribute("data-id", a[l]), i.innerHTML = "Selected record ID: " + a[l], n.appendChild(i)
                        }

                        $.ajax({
                                method: "POST",
                                url: tableConf.cmdUrl,
                                data: {ids: ids, command: $("#data_command").val()},
                                dataType: "json",
                            })
                            .done(function(res) {
                                e.search("ok", "refresh");
                            }).fail(function() {
                                alert( "Connection error" );
                            })
                    })
            }()
        }
    }
}();
jQuery(document).ready(function() {
    DatatableRecordSelectionDemo.init()
});