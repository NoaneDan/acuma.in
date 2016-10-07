_.templateSettings = {
    interpolate: /\{\{(.+?)\}\}/g
};

var twitter_img_template = _.template(
    '<div class="box lg-box" style="background-image:url(\'{{ media_url }}\');">' +
        '<img src="/assets/images/1x1.gif" data-image="{{ media_url }}" data-caption="{{ text }}">' +
        '<div title ="Report inappropriate content" class="report" id="{{ report_id }}"><i class="flag icon-flag">&#xe800;</i></div>' +
        '<div class="lg-title">' +
            '<h2><a href="{{ url }}" target="_blank">{{ username }}</a></h2>' +
            '<a href="{{ url }}" target="_blank"><i class="icon icon-twitter">&#xf099;</i>' +
            '</a>' +
            '<p>{{ text }}</p>' +
        '</div>' +
    '</div>'
);
var twitter_slider_template = _.template(
    '<div class="box lg-box">' +					
        '<div class="slick-gallery">' +
            '{{ img_posts }}' +					
        '</div>' +
        '<div class="pagination">' +
            '<div class="pagingInfo">{{ pagination }}</div>' +
        '</div>' +
    '</div>'
);
var twitter_slider_element_template = _.template(
    '<div class="item" id="{{ id }}" style="background-image:url(\'{{ media_url }}\');">' +
        '<img src="/assets/images/1x1.gif" data-image="{{ media_url }}" data-caption="{{ text }}">' +
        '<div title ="Report inappropriate content" class="report" id="{{ report_id }}"><i class="flag icon-flag">&#xe800;</i></div>' +
        '<div class="lg-title">' +
            '<h2><a href="{{ url }}" target="_blank">{{ username }}</a></h2>' +
            '<a href="{{ url }}" target="_blank"><i class="icon icon-twitter">&#xf099;</i>' +
            '</a>' +
            '<p>{{ text }}</p>' +
        '</div>' +
    '</div>'
);
var ig_template = _.template(
    '<div class="box sm-box" id="{{ id }}" style="background-image:url(\'{{ media_url }}\');>' +
        '<img src="/assets/images/1x1.gif" data-image="{{ media_url }}" data-caption="{{ text }}">' +
        '<div title ="Report inappropriate content" class="report" id="{{ report_id }}"><i class="flag icon-flag">&#xe800;</i></div>' +
        '<div class="sm-title">' +
            '<h2><a href="{{ url }}" target="_blank">@{{ username }}</a></h2>' +
            '<a href="{{ url }}" target="_blank"><i class="icon icon-instagram">&#xf16d;</i>' +
            '</a>' +
            '<p>{{ text }}</p>' +
        '</div>' +
    '</div>' 
);
var fb_event_template = _.template(
    '<div class="box lg-box" id="{{ id }}">' +
        '<div class="slick-gallery">' +
            '{{ photos }}' +
        '</div>' +
        '<div class="pagination">' +
            '<div class="pagingInfo"></div>' +
        '</div>' +
        '<div class="lg-title">' +
            '<h2><a href="{{ facebook_url }}" target="_blank">{{ name }}</a></h2>' +
            '<i class="icon icon-link" title="Copy link to clipboard" data-clipboard-text="' + location.origin + '/Oradea/{{ id }}">&#xe803;</i>' +
            '<p><i class="icon-calendar-empty">&#xf133;</i>{{ start_date }}&nbsp;&nbsp;<i class="icon-clock">&#xe801;</i>{{ start_time }}<br> <i class="icon-location">&#xe802;</i>{{ location }} &nbsp;&nbsp; <br class="break"/> Attending: {{ attending }} Interested: {{ interested }} Maybe: {{ maybe }} </p>' +
        '</div>' +
    '</div>'
);
var fb_photo_template = _.template(
     '<div class="item" style="background-image:url(\'{{ photo_url }}\')">' + 
        '<img src="/assets/images/1x1.gif" data-image="{{ photo_url }}" data-caption="{{ name }}">' +
    '</div>'
);                    
var tweets_slider_template = _.template(
    '<div class="box rec-box">' +					
        '<div class="slick-gallery">' +
            '{{ tweets }}' +					
        '</div>' +
        '<div class="pagination">' +
            '<div class="pagingInfo">{{ pagination }}</div>' +
        '</div>' +
    '</div>'
);
var tweet_slider_element_template = _.template(
    '<div class="item" id="{{ id }}">' +
        '<div class="tweet-content">' +
            '<div title ="Report inappropriate content" class="report" id="{{ report_id }}"><i class="flag icon-flag">&#xe800;</i></div>' +
            '<div class="tweet-header">' +
                '<div class="tweet-avatar"><img src="{{ profile_img }}"></div>' +
                '<h2><a href="{{ url }}" target="_blank">{{ name }}</a></h2>' +
                '<h3><a href="{{ url }}" target="_blank">@{{ username }}</a></h3>' +
            '</div>' +
            '<div class="tweet-body">' +
                '<p class="tweet-text">{{ text }}</p>' +
            '</div>' +
            '<div class="tweet-footer">' +
                '<p class="tweet-date">{{ created_at }}</p>' +
                '<a href="{{ url }}" target="_blank"> <i class="blue icon-twitter">&#xf099;</i></i></a>' +
            '</div>' +
        '</div>' +
    '</div>'
);
var tweet_template = _.template(
    '<div class="box rec-box" id="{{ id }}">' +
        '<div class="tweet-content">' +
            '<div title="Report inappropriate content" class="report" id="{{ report_id }}"><i class="flag icon-flag">&#xe800;</i></div>' +
            '<div class="tweet-header">' +
                '<div class="tweet-avatar"><img src="{{ profile_img }}"></div>' +
                '<h2><a href="{{ url }}" target="_blank">{{ name }}</a></h2>' +
                '<h3><a href="{{ url }}" target="_blank">@{{ username }}</a></h3>' +
            '</div>' +
            '<div class="tweet-body">' +
                '<p class="tweet-text">{{ text }}</p>' +
            '</div>' +
            '<div class="tweet-footer">' +
                '<p class="tweet-date">{{ created_at }}</p>' +
                '<a href="{{ url }}" target="_blank"> <i class="blue icon-twitter">&#xf099;</i></a>' +
            '</div>' +
        '</div>' +
    '</div>'
);

function loadMore() {
    request = {
        "max_id": window.max_id
    };

    $('.loading').show();
    $.ajax({
        type: "POST",
        url: "/load_more",
        data: JSON.stringify(request),
        dataType: "text",
        success: function (data) {
            $('.loading').hide();   
            
            loadPosts(data);
        }
    });
}


function loadPosts(data) {
    
    addData(data);
    initGallery();
    initReportButtons();
}


function addData(data) {
    
    try {
        data = JSON.parse(data);       
    } catch (e) {

    }
    
    if (data.posts === []) {
        return;
    } 
    
    window.max_id = data.max_id;
    window.nrOfPosts = data.nrOfPosts;
    
    if (window.max_id === window.nrOfPosts) {
        $('.more').html('That\'s all folks!').addClass('all-done').attr('disabled','disabled');
        
    }
    
    var container = $('.grid');
    var posts = '';
    for (var key in data.posts) {
        
        if (!data.posts.hasOwnProperty(key)) {
            continue;
        }
        
        var source = key.split('-')[0];
        switch (source) {
            case 'twitter_post':
                posts += getTwitterMediaPosts(data.posts[key]);
                
                break;
            case 'twitter_tweet':
                posts += getTweets(data.posts[key]);
                
                break;
            case 'ig_post':
                posts += getInstagramPosts(data.posts[key]);
                
                break;
            case 'fb_event':
                posts += getFacebookEvents(data.posts[key]);
                
                break;
            default:
                break;
        }
    }
    
    posts = $(posts);
    container.append(posts).isotope('appended', posts).isotope('reloadItems').isotope();
}



function initGallery() {
    
    // init gallary
    $('.slick-gallery').each(function() {
        $(this).not('.slick-initialized').slick({
            autoplay: true,
            autoplaySpeed: 4000,
            adaptiveHeight: true
        });
        $(this).on('init reInit afterChange', function(event, slick){
            var box_width = $('.box.lg-box').width(),
                box_height = $('.box.lg-box').outerHeight(),
                recbox_width = $('.box.rec-box').width(),
                recbox_height = $('.box.rec-box').outerHeight(),
                i = (slick.currentSlide ? slick.currentSlide : 0) + 1,
                $status = $(this).parent().find('.pagination .pagingInfo');
    
            $(this).find('.item.slick-slide img').each(function() {
                $(this).css({'width':box_width,'height':box_height});
            });
    
            $(this).find('.item.slick-slide .tweet-content').each(function() {
                $(this).css({'width':recbox_width,'height':recbox_height});
            });     
    
            $status.text(i + ' / ' + slick.slideCount);
        });
        $(this).slickLightbox({
            src: 'data-image',
            itemSelector: '.item img',
            useHistoryApi: 'true',
            caption: 'caption'
        });
    });
}



function initReportButtons() {
    
    // init report buttons
    $('.report').click(function () {
        
        $.ajax({
            type: "POST",
            url: "/report",
            data: $(this).attr('id'),
            dataType: "text",
            success: function () {
                $.notify(
                    'Post reported!',
                    'success',
                    { position: "top right" }
                );
            },
            error: function () {
                $.notify(
                    'Failed to report!',
                    'error',
                    { position: "top right" }
                );
            }
        });
    });
    $('.report').attr('class', 'report-initialized');
}


function getFacebookEvents(events) {
    
    var fb_events = _.map(events, function (event) {
        var photos =  _.map(event.photos, function (photo) {
            return fb_photo_template({
                photo_url: photo,
                name: event.name
            });
        });
        
        if (_.isEmpty(photos)) {
            photos = [fb_photo_template({
                photo_url: '/assets/images/no-image.jpg',
                name: event.name
            })];
        }
        
        return fb_event_template({
            photos: photos.join("\n"),
            name: event.name,
            start_time: event.start_time,
            start_date: event.start_date,
            location: event.location,
            facebook_url: event.facebook_url,
            attending: event.attending_count,
            interested: event.interested_count,
            maybe: event.maybe_count,
            pagination: '1 / ' + photos.length,
            id: event.id
        });
    });
    
    return fb_events.join("\n");
}

function getTwitterMediaPosts(group) {
    
    if (group.length == 1) {
        post = group[0];
        var twitter_media_posts = twitter_img_template({
            media_url: post.media_url,
            url: post.url,
            username: post.username,
            text: post.text,
            hashtags: post.hashtags,
            id: post.id,
            report_id: post.report_id
        });
    }
    else {
        elements = _.map(group, function (post) {
            return twitter_slider_element_template({
                media_url: post.media_url,
                url: post.url,
                username: post.username,
                text: post.text,
                hashtags: post.hashtags,
                id: post.id,
                report_id: post.report_id
            });
        });
        
        var twitter_media_posts = twitter_slider_template({
            img_posts: elements.join("\n"),
            pagination: '1 / ' + elements.length
        });
    }
    
    return twitter_media_posts;
}

function getInstagramPosts(group) {

    var post = group[0];
    var instagram_posts = ig_template({
        media_url: post.media_url,
        url: post.instagram_url,
        username: post.username,
        text: post.text,
        hashtags: post.hashtags,
        id: post.id,
        report_id: post.report_id
    });
    
    return instagram_posts;
}

function getTweets(group) {
    
    if (group.length == 1) {
        tweet = group[0];
        var twitter_tweets =  tweet_template({
            id: tweet.id,
            url: tweet.url,
            username: tweet.username,
            name: tweet.name || tweet.username,
            text: tweet.text,
            hashtags: tweet.hashtags,
            created_at: tweet.created_at,
            report_id: tweet.report_id,
            profile_img: tweet.profile_img
        });
    }
    else {
        elements = _.map(group, function (tweet) {
            return tweet_slider_element_template({
                id: tweet.id,
                url: tweet.url,
                username: tweet.username,
                name: tweet.name || tweet.username,
                text: tweet.text,
                hashtags: tweet.hashtags,
                created_at: tweet.created_at,
                report_id: tweet.report_id,
                profile_img: tweet.profile_img
            });
        });
        
        var twitter_tweets = tweets_slider_template({
            tweets: elements.join("\n"),
            pagination: '1 / ' + elements.length
        });
    }
    
    return twitter_tweets;
}
