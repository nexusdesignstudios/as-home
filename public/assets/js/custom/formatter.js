function imageFormatter(value, row) {
    if (value) {
        var svg_clr_setting = row.svg_clr;
        if (svg_clr_setting != null && svg_clr_setting == 1) {
            var imageUrl = value;
            if (value) {
                if (imageUrl.split('.').pop() === 'svg') {
                    return '<embed class="svg-img" src="' + value + '">';
                } else {
                    return '<a class="image-popup-no-margins" href="' + value + '"><img class="rounded avatar-md shadow img-fluid" alt="" src="' + value + '" width="55"></a>';
                }
            }
        } else {
            return (value !== '') ? '<a class="image-popup-no-margins" href="' + value + '"><img class="rounded avatar-md shadow img-fluid" alt="" src="' + value + '" width="55"></a>' : '';
        }
    }
    return null;

}

function sub_category(value, row) {
    return '<a href="get_sub_categories/' + row.id + '"> <div class="category_count">' + value + ' Sub Categories</div></a>';
}

function custom_fields(value, row) {

    var rootUrl = window.location.protocol + '//' + window.location.host;
    return '<a href="' + rootUrl + '/category_custom_fields/' + row.id + '"> <div class="category_count">' + value + ' Custom Fields</div></a>';

}


function premium_status_switch(value, row) {
    var status;
    if (row.added_by == 'Admin') {
        status = value == "1" ? "checked" : "";
        return `<div class="form-check form-switch" style="padding-left: 5.2rem;">
                    <input class = "form-check-input switch1" id = "${row.id}" onclick = "chk(this);" data-url="updateaccessability" type = "checkbox" role = "switch"${status} value = ${value}>
                </div>`;
    } else {
        status = value == "1" ? "checked" : "";
        return `<div class="form-check form-switch" style="padding-left: 5.2rem;">
                    <input disabled class = "form-check-input switch1" id = "${row.id}" type = "checkbox" role = "switch"${status} value = ${value}>
                </div>`;
    }
}


function badge(value, row) {
    if (value == "review") {
        badgClass = 'primary';
        badgeText = 'Under Review';
    }
    if (value == "approve") {
        badgClass = 'success';
        badgeText = 'Approved';
    }
    if (value == "reject") {
        badgClass = 'danger';
        badgeText = 'Rejected';
    }
    return '<span class="badge rounded-pill bg-' + badgClass +
        '">' + badgeText + '</span>';
}





function propertyTypeFormatter(value, row) {
    if (row.property_type_raw == 0) {
        return '<div class="sell_type">' + (window.trans && window.trans["Sell"] ? window.trans["Sell"] : 'Sell') + '</div>';
    } else if (row.property_type_raw == 1) {
        return '<div class="rent_type">' + (window.trans && window.trans["Rent"] ? window.trans["Rent"] : 'Rent') + '</div>';
    } else if (row.property_type_raw == 2) {
        return (window.trans && window.trans["Sold"] ? window.trans["Sold"] : 'Sold');
    } else if (row.property_type_raw == 3) {
        return (window.trans && window.trans["Rented"] ? window.trans["Rented"] : 'Rented');
    }
}



function status_badge(value, row) {
    if (value == '0') {
        badgClass = 'danger';
        badgeText = 'OFF';
    } else {
        badgClass = 'success';
        badgeText = 'ON';
    }
    return '<span class="badge rounded-pill bg-' + badgClass +
        '">' + badgeText + '</span>';
}

function user_status_badge(value, row) {
    if (value == '0') {
        badgClass = 'danger';
        badgeText = 'Inacive';
    } else {
        badgClass = 'success';
        badgeText = 'Active';
    }
    return '<span class="badge rounded-pill bg-' + badgClass +
        '">' + badgeText + '</span>';
}

function style_app(value, row) {
    return '<a class="image-popup-no-margins" href="images/app_styles/' + value + '.png"><img src="images/app_styles/' + value + '.png" alt="style_4"  height="60" width="60" class="rounded avatar-md shadow img-fluid"></a>';
}

function filters(value) {


    if (value == "most_liked") {

        filter = "Most Liked";
    } else if (value == "price_criteria") {
        filter = "Price Criteria";
    } else if (value == "category_criteria") {
        filter = "Category Criteria";
    } else if (value == "most_viewed") {
        filter = "Most Viewed";
    }
    return filter;
}

function adminFile(value, row) {
    return "<a href='languages/" + row.code + ".json ' )+' > View File < /a>";

}

function appFile(value, row) {
    return "<a href='lang/" + row.code + ".json ' )+' > View File < /a>";
}

function enableDisableFeaturedPropertiesFormatter(value, row) {
    let status = row.status == '1' ? 'checked' : '';
    return `<div class="form-check form-switch center" style="margin-top: 10%;padding-left: 5.2rem;">
                <input class="form-check-input switch1" id="${row.id}"  onclick="chk(this);" type="checkbox" role="switch" ${status} '>
            </div>`
}

function featuredPropertiesDataFormatter(value, row) {
    return `<div class="featured_property">
                <div class="image-container">
                    <img src="${row.title_image}" alt="Image">
                    <div class="featured-property-type"> ${window.trans && window.trans[row.type] ? window.trans[row.type] : row.type} </div>
                </div>
            <div>
            <div class="d-flex">
                <img src="${row.category.image}" alt="Image" height="24px" width="24px">
                <div class="category"> ${row.category.category} </div>
            </div>
            <div class="title"> ${row.title} </div>
            <div class="price"> ${row.price} </div>
            <div class="city">
                <i class="bi bi-geo-alt"></i>
                ${row.city}
            </div>`;
}

function enableDisableSwitchFormatter(value, row) {
    if (value != null) {
        let status = (value == "1" || value == "active") ? "checked" : "";
        return `<div class="form-check form-switch" text-center style="display: flex; justify-content: center;">
            <input class = "form-check-input switch1"id = "${row.id}" onclick = "chk(this);" data-url="${row.edit_status_url}" type="checkbox" role="switch" ${status} value="${value}">
            </div>`;
    }
    return null;
}

function yesNoStatusFormatter(value) {
    let text = "";
    let classType = "";
    if (value == 1) {
        text = (window.trans && window.trans["Yes"]) ? window.trans["Yes"] : "Yes";
        classType = 'success'
    } else {
        text = (window.trans && window.trans["No"]) ? window.trans["No"] : "No";
        classType = 'danger'
    }
    return '<span class="badge rounded-pill bg-' + classType + '">' + text + '</span>';
}

function enableDisableCityImageSwitchFormatter(value, row) {
    let disabled = row.exclude_status_toggle == 1 ? 'disabled' : '';
    let status = (value == "1") ? "checked" : "";
    return `<div class="form-check form-switch" text-center style="display: flex; justify-content: center;">
                <input class = "form-check-input switch1"id = "${row.id}" onclick = "chk(this);" data-url="${row.edit_status_url}" type="checkbox" role="switch" ${status} value="${value}" ${disabled}>
            </div>`;
}

function statusFormatter(value, row) {
    if (value == '1') {
        badgClass = 'success';
        badgeText = (window.trans && window.trans['Active']) ? window.trans['Active'] : 'Active';
    } else {
        badgClass = 'danger';
        badgeText = (window.trans && window.trans['Inactive']) ? window.trans['Inactive'] : 'Inactive';
    }
    return '<span class="badge rounded-pill bg-' + badgClass + '">' + badgeText + '</span>';
}


function videoLinkFormatter(value) {
    if (value) {
        return `<a href="${value}" target="_blank">${(window.trans && window.trans['Video Link']) ? window.trans['Video Link'] : 'Video Link'}</a>`
    }
    return null;
}

function projectTypeFormatter(value) {
    if (value == 'upcoming') {
        return `${(window.trans && window.trans['Upcoming']) ? window.trans['Upcoming'] : 'Upcoming'}`
    }
    if (value == 'under_construction') {
        return `${(window.trans && window.trans['Under Construction']) ? window.trans['Under Construction'] : 'Under Construction'}`
    }
    return null;
}

function fieldTypeFormatter(value) {
    return value.charAt(0).toUpperCase() + value.slice(1).toLowerCase();
}

function fieldValuesFormatter(value) {
    let html = '';
    if (value && value.length) {
        html += `<ul style="margin-bottom:0px; padding-left:0px;">`
        $.each(value, function (index, valueData) {
            html += `<i class='fa fa-arrow-right' aria-hidden='true'></i> ${valueData.value}<br>`
        });
        html += `</ul>`
    } else {
        html = '<div>-</div>';
    }
    return html;

}

function userNameProfileFormatter(value) {
    return `<div class="row align-items-center">
                <div class="col-md-2">
                    <a class="image-popup-no-margins" href="${value.profile}"><img class="rounded avatar-md shadow img-fluid" alt="" src="${value.profile}" width="55"></a>
                </div>
                <div class="ml-2 col-md-10">
                    <span>${value.name}</span>
                </div>
            </div>`;
}


function verifyCustomerStatusFormatter(value) {
    if (value == 'success') {
        badgeClass = 'success';
        badgeText = (window.trans && window.trans['Success']) ? window.trans['Success'] : 'Success';
    } else if (value == 'failed') {
        badgeClass = 'danger';
        badgeText = (window.trans && window.trans['Failed']) ? window.trans['Failed'] : 'Failed';
    } else {
        badgeClass = 'warning';
        badgeText = (window.trans && window.trans['Pending']) ? window.trans['Pending'] : 'Pending';
    }
    return '<span class="badge rounded-pill bg-' + badgeClass + '">' + badgeText + '</span>';
}

function requestStatusFormatter(value, row) {
    if (value == 'approved') {
        badgeClass = 'success';
        badgeText = (window.trans && window.trans['Approved']) ? window.trans['Approved'] : 'Approved';
    } else if (value == 'rejected') {
        badgeClass = 'danger';
        badgeText = (window.trans && window.trans['Rejected']) ? window.trans['Rejected'] : 'Rejected';
    } else {
        badgeClass = 'warning';
        badgeText = (window.trans && window.trans['Pending']) ? window.trans['Pending'] : 'Pending';
    }
    return '<span class="badge rounded-pill bg-' + badgeClass + '">' + badgeText + '</span>';
}
function customerLoginTypeFormatter(value) {
    if (value == 0) {
        badgeText = `<i class="bi bi-google" aria-hidden="true"></i>`
        badgeClass = "danger"
    } else if (value == 1) {
        badgeText = `<i class="fa fa-phone" aria-hidden="true"></i>`
        badgeClass = "success"
    } else if (value == 2) {
        badgeText = `<i class="bi bi-apple" aria-hidden="true"></i>`
        badgeClass = "secondary"
    } else if (value == 3) {
        badgeText = `<i class="fa fa-envelope" aria-hidden="true"></i>`
        badgeClass = "warning"
    }
    return '<span class="badge rounded-pill bg-' + badgeClass + '">' + badgeText + '</span>';
}

function projectEnableDisableSwitchFormatter(value, row) {
    if (row.is_admin_listing == 1 && value != null) {
        let status = (value == "1" || value == "active") ? "checked" : "";
        return `<div class="form-check form-switch" text-center style="display: flex; justify-content: center;">
            <input class = "form-check-input switch1"id = "${row.id}" onclick = "chk(this);" data-url="${row.edit_status_url}" type="checkbox" role="switch" ${status} value="${value}">
            </div>`;
    }
    return null;
}


function enableDisableFeaturedPropertiesFormatter(value, row) {
    let status = row.status == '1' ? 'checked' : '';
    return `<div class="form-check form-switch center" style="margin-top: 10%;padding-left: 5.2rem;">
                <input class="form-check-input switch1" id="${row.id}"  onclick="chk(this);" type="checkbox" role="switch" ${status} '>
            </div>`
}

function packageTypeFormatter(value) {
    let text = "";
    let classType = "";
    if (value == 'paid') {
        text = (window.trans && window.trans["Paid"]) ? window.trans["Paid"] : "Paid";
        classType = 'success'
    } else {
        text = (window.trans && window.trans["Free"]) ? window.trans["Free"] : "Free";
        classType = 'warning'
    }
    return '<span class="badge rounded-pill bg-' + classType + '">' + text + '</span>';
}

function packageFeaturesFormatter(value) {
    let html = ``;
    $.each(value, function (index, data) {
        html += `<span>${index + 1}. ${data.feature.name}</span><br>`;
    });

    return html;

}

function paymentStatusFormatter(value) {
    if (value == 'success') {
        text = (window.trans && window.trans["Success"]) ? window.trans["Success"] : "Success";
        classType = 'success'
    } else if (value == 'failed') {
        text = (window.trans && window.trans["Failed"]) ? window.trans["Failed"] : "Failed";
        classType = 'danger'
    } else if (value == 'review') {
        text = (window.trans && window.trans["Review"]) ? window.trans["Review"] : "Review";
        classType = 'info'
    } else if (value == 'rejected') {
        text = (window.trans && window.trans["Rejected"]) ? window.trans["Rejected"] : "Rejected";
        classType = 'secondary'
    } else {
        text = (window.trans && window.trans["Pending"]) ? window.trans["Pending"] : "Pending";
        classType = 'warning'
    }
    return '<span class="badge rounded-pill bg-' + classType + '">' + text + '</span>';
}

function advertisementTypeFormatter(value) {
    if (value == 'property') {
        text = (window.trans && window.trans["Property"]) ? window.trans["Property"] : "Property";
        classType = 'primary';
    } else if (value == 'project') {
        text = (window.trans && window.trans["Project"]) ? window.trans["Project"] : "Project";
        classType = 'secondary';
    } else {
        return null;
    }
    return '<span class="badge rounded-pill bg-' + classType + '">' + text + '</span>';
}

function packagePriceFormatter(value, row) {
    if (value == null) {
        return '<span class="badge rounded-pill bg-warning">' + (window.trans && window.trans["Free"] ? window.trans["Free"] : "Free") + '</span>';
    }
    return row.price_symbol + ' ' + value;
}

function paymentAmountFormatter(value, row) {
    return row.price_symbol + ' ' + value;
}
