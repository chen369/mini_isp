<?php
/***************************************************************************
 *                              auction_room.php
 *                            -------------------
 *   begin                :   January 2004
 *   copyright            :   (C) FR
 *   email                :   fr@php-styles.com
 *   Last update          :   July 2004 - FR
 *
 ***************************************************************************/

/***************************************************************************
 *
 *   This hack is released under the GPL License. 
 *   This hack can be freely used, but not distributed, without permission. 
 *   Intellectual Property is retained by the author listed above. 
 *
 ***************************************************************************/

     define('IN_PHPBB', true);
     $phpbb_root_path = './';
     include_once($phpbb_root_path . 'auction/auction_common.php');

     // Start session management
     $userdata = session_pagestart($user_ip, AUCTION_ROOM);
     init_userprefs($userdata);
     // End session management

     // Check permissions for auction
     checkPermission('VIEW_ALL');
     
     //   Information for the standard Who-is-Online-Block
     if( $total_posts == 0 )
          {
               $l_total_post_s = $lang['Posted_articles_zero_total'];
          }
     else if( $total_posts == 1 )
          {
               $l_total_post_s = $lang['Posted_article_total'];
          }
     else
          {
               $l_total_post_s = $lang['Posted_articles_total'];
          }

     if( $total_users == 0 )
          {
               $l_total_user_s = $lang['Registered_users_zero_total'];
          }
     else if( $total_users == 1 )
          {
               $l_total_user_s = $lang['Registered_user_total'];
          }
     else
          {
               $l_total_user_s = $lang['Registered_users_total'];
          }
      // End information for standard Who-is-online-Block


     // Start initial var setup
     if ( isset($HTTP_GET_VARS[POST_AUCTION_ROOM_URL]) || isset($HTTP_POST_VARS[POST_AUCTION_ROOM_URL]) )
         {
             $auction_room_id = ( isset($HTTP_GET_VARS[POST_AUCTION_ROOM_URL]) ) ? intval($HTTP_GET_VARS[POST_AUCTION_ROOM_URL]) : intval($HTTP_POST_VARS[POST_AUCTION_ROOM_URL]);
         }
     else if ( isset($HTTP_GET_VARS['forum']))
         {
             $auction_room_id = intval($HTTP_GET_VARS['forum']);
         }
     else
         {
             $auction_room_id = '';
         }

     $start = ( isset($HTTP_GET_VARS['start']) ) ? intval($HTTP_GET_VARS['start']) : 0;

     if ( isset($HTTP_GET_VARS['mark']) || isset($HTTP_POST_VARS['mark']) )
          {
               $mark_read = (isset($HTTP_POST_VARS['mark'])) ? $HTTP_POST_VARS['mark'] : $HTTP_GET_VARS['mark'];
          }
     else
          {
               $mark_read = '';
          }
     // End initial var setup

     // Begin check if auction_room_id is set
     if ( !empty($auction_room_id) )
          {
               $sql = "SELECT *
                       FROM " . AUCTION_ROOM_TABLE . "
                       WHERE PK_auction_room_id = $auction_room_id";
               if ( !($result = $db->sql_query($sql)) )
                    {
                         message_die(GENERAL_ERROR, 'Could not obtain auction-room-information', '', __LINE__, __FILE__, $sql);
                    }
          }
     else
          {
               message_die(GENERAL_MESSAGE, $lang['auction_room_does_not_exist']);
          }
     // End check if auction_room_id is set

     // Begin check if auction_room exists
     if ( !($auction_room_row = $db->sql_fetchrow($result)) )
          {
               message_die(GENERAL_MESSAGE, $lang['auction_room_does_not_exist']);
          }
     // END check if auction_room exists

     // Get auction-room name
     // START Grab all the offer-data
     $sql = "SELECT auction_room_title
             FROM " . AUCTION_ROOM_TABLE . "
             WHERE PK_auction_room_id = " . $auction_room_id;

     if ( !($result = $db->sql_query($sql)) )
          {
               message_die(GENERAL_ERROR, 'Could not get auction-room name', '', __LINE__, __FILE__, $sql);
          }

     $row = $db->sql_fetchrow($result);
     $auction_room_title = $row['auction_room_title'];
     
     if( isset($HTTP_POST_VARS['order']) || isset($HTTP_GET_VARS['order']) )
          {
               $order = ( isset($HTTP_POST_VARS['order']) ) ? $HTTP_POST_VARS['order'] : $HTTP_GET_VARS['order'];
               $order = htmlspecialchars($order);
          }
     else
          {
               $order = "";
          }
     $order_string = " t.auction_offer_time_stop";
     if( !empty($order) )
     {
         switch($order)
         {
             case 'price_asc':
                             $order_string = " t.auction_offer_last_bid_price ASC";
                   break;
             case 'price_desc':
                             $order_string = " t.auction_offer_last_bid_price DESC";
                   break;
             case 'price_start_desc':
                             $order_string = " t.auction_offer_price_start DESC";
                   break;
             case 'price_start_asc':
                             $order_string = " t.auction_offer_price_start ASC";
                   break;
             case 'title_asc':
                             $order_string = " t.auction_offer_title ASC";
                   break;
             case 'title_desc':
                             $order_string = " t.auction_offer_title DESC";
                   break;
             default :
                             $order_string = " t.auction_offer_time_stop";
                   break;

         }
     }

     // START Grab all the offer-data
     if ( $start < 1 )
          {
               $pagination_start = 0;
               $pagination_end = $pagination_start+$auction_config_data['auction_room_pagination'];
          }
     else
          {
               $pagination_start = $start;
               $pagination_end = $pagination_start+$auction_config_data['auction_room_pagination']-1;
          }

     
     $sql = "SELECT t.*,
                    u.username,
                    u.user_id,
                    u2.username as maxbidder_user_name,
                    u2.user_id as maxbidder_user_id
                      FROM (" . AUCTION_OFFER_TABLE . " t
                      LEFT JOIN " . USERS_TABLE . " u ON u.user_id = t.FK_auction_offer_user_id
                      LEFT JOIN " . USERS_TABLE . " u2 ON u2.user_id = t.FK_auction_offer_last_bid_user_id)
                      WHERE t.FK_auction_offer_room_id = " . $auction_room_id . "
                           AND auction_offer_time_stop>" . time() . "
                           AND auction_offer_time_start<" . time() . "
                           AND auction_offer_paid = 1
                           AND auction_offer_state = " . AUCTION_OFFER_UNLOCKED . "
                      ORDER BY " . $order_string . "
                      LIMIT " . $pagination_start . "," . $pagination_end . "";

     if ( !($result = $db->sql_query($sql)) )
          {
               message_die(GENERAL_ERROR, 'Could not obtain room information', '', __LINE__, __FILE__, $sql);
          }

     $total_offers = 0;
     while( $row = $db->sql_fetchrow($result) )
          {
               $auction_offer_rowset[] = $row;
               $total_offers++;
          }
     $db->sql_freeresult($result);
     $total_offers += $total_announcements;
     // END Grab all necessary bid-data

     // START UPDATE VIEW-ROOM-COUNT
     $sql = "UPDATE " . AUCTION_ROOM_TABLE . "
             SET auction_room_count_view = auction_room_count_view + 1
             WHERE PK_auction_room_id = " . $HTTP_GET_VARS[POST_AUCTION_ROOM_URL] . "";

     if( !($result = $db->sql_query($sql)) )
         {
              message_die(GENERAL_ERROR, 'Could not update room views', '', __LINE__, __FILE__, $sql);
         }
     // END UPDATE VIEW-ROOM-COUNT

     // Include-Blocks
     includeTickerBlock();
     includeAuctionRoomBlock();
     includeCloseToEndBlock();
     includeStatisticBlock();
     includeMyAuctionsBlock($userdata);
     includeCalendarBlock();
     includeSearchBlock();
     includeTermsBlock();
     includeNewsBlock();
     includeAuctionSpecialBlock();
     includeAuctionDropDownRoomBlock();
     includeLastBidsBlock();
     includeNewestOffersBlock();

     // Include Header
     $page_title = $lang['auction_room_view'] . ' - ' . $auction_room_row['auction_room_title'];
     include($phpbb_root_path . 'includes/page_header.'.$phpEx);
     include($phpbb_root_path . 'auction/auction_header.'.$phpEx);

     // Not activated as it is not fully working
     //room_pagination($auction_room_id,$start);

     $template->set_filenames(array('body' => 'auction_viewroom_body.tpl'));
     $template->assign_vars(array(
        'TOTAL_POSTS' => sprintf($l_total_post_s, $total_posts),
        'TOTAL_USERS' => sprintf($l_total_user_s, $total_users),
        'NEWEST_USER' => sprintf($lang['Newest_user'], '<a href="' . append_sid("profile.$phpEx?mode=viewprofile&amp;" . POST_USERS_URL . "=$newest_uid") . '">', $newest_user, '</a>'),
        'L_ONLINE_EXPLAIN' => $lang['Online_explain'],
        'U_POST_NEW_OFFER' => append_sid("auction_offer.$phpEx?mode=add&" . POST_AUCTION_ROOM_URL . "=" . $auction_room_id),
        'AUCTION_ROOM_ID' => $auction_room_id,
        'AUCTION_ROOM_TITLE' => $auction_room_title,
        'AUCTION_ROOM_DESCRIPTION'=> $auction_room_row['auction_room_description'],
        'AUCTION_ROOM_ICON' => ( $auction_room_row['auction_room_icon'] ) ? "auction/images/" . $auction_room_row['auction_room_icon'] : $phpbb_root_path . $images['auction_open'],
        'L_AUCTION_SORT_PRICE_ASC' => $lang['auction_sort_price_asc'],
        'L_AUCTION_SORT_PRICE_DESC' => $lang['auction_sort_price_desc'],
        'L_AUCTION_SORT_PRICE_START_ASC' => $lang['auction_sort_price_start_asc'],
        'L_AUCTION_SORT_PRICE_START_DESC' => $lang['auction_sort_price_start_desc'],
        'L_AUCTION_SORT_TITLE_ASC' => $lang['auction_sort_title_asc'],
        'L_AUCTION_SORT_TITLE_DESC' => $lang['auction_sort_title_desc'],
        'U_AUCTION_SORT_PRICE_ASC' => append_sid("auction_room.$phpEx?order=price_asc&" . POST_AUCTION_ROOM_URL . "=" . $auction_room_id),
        'U_AUCTION_SORT_PRICE_DESC' => append_sid("auction_room.$phpEx?order=price_desc&" . POST_AUCTION_ROOM_URL . "=" . $auction_room_id),
        'U_AUCTION_SORT_PRICE_START_ASC' => append_sid("auction_room.$phpEx?order=price_start_asc&" . POST_AUCTION_ROOM_URL . "=" . $auction_room_id),
        'U_AUCTION_SORT_PRICE_START_DESC' => append_sid("auction_room.$phpEx?order=price_start_desc&" . POST_AUCTION_ROOM_URL . "=" . $auction_room_id),
        'U_AUCTION_SORT_TITLE_ASC' => append_sid("auction_room.$phpEx?order=title_asc&" . POST_AUCTION_ROOM_URL . "=" . $auction_room_id),
        'U_AUCTION_SORT_TITLE_DESC' => append_sid("auction_room.$phpEx?order=title_desc&" . POST_AUCTION_ROOM_URL . "=" . $auction_room_id),
        'POST_IMG' => ( $auction_room_row['auction_room_state'] == AUCTION_ROOM_LOCKED ) ? $images['post_locked'] : $images['post_new'],
        'L_AUCTION_OFFERS' => $lang['auction_offers'],
        'L_AUCTION_OFFER_OFFERER' => $lang['auction_offer_offerer'],
        'L_AUCTION_OFFER_VIEWS' => $lang['auction_offer_views'],
        'L_AUCTION_FIRST_PRICE' => $lang['auction_offer_first_price'],
        'L_AUCTION_LAST_PRICE' => $lang['auction_offer_last_price'],
        'L_AUCTION_LAST_OFFER' => $lang['auction_last_offer'],
        'L_AUCTION_OFFER_TIME_STOP' => $lang['auction_offer_time_stop'],
        'L_AUCTION_OFFER_LAST_BID_USER' => $lang['auction_offer_last_bid_user'],
        'L_AUCTION_OFFER_OFFERER' => $lang['auction_offerer'],
        'NEW_OFFER_IMAGE' => $images['newoffer'],
        'U_VIEW_FORUM' => append_sid("auction_room.$phpEx?" . POST_AUCTION_ROOM_URL ."=$auction_room_id"),
        'MODAUTHOR' => $lang['modauthor'],
        'MODPOWERED' => $lang['modpowered']));

// Dump out the page
if( $total_offers )
{
    for($i = 0; $i < $total_offers; $i++)
    {
        $auction_offer_id = $auction_offer_rowset[$i]['PK_auction_offer_id'];
        $auction_offer_title = ( count($orig_word) ) ? preg_replace($orig_word, $replacement_word, $auction_offer_rowset[$i]['auction_offer_title']) : $auction_offer_rowset[$i]['auction_offer_title'];
        $view_auction_offer_url = append_sid("auction_offer_view.$phpEx?" . POST_AUCTION_OFFER_URL . "=$auction_offer_id");
        $offerer = ( $auction_offer_rowset[$i]['FK_auction_offer_user_id'] != ANONYMOUS ) ? '<a href="' . append_sid("profile.$phpEx?mode=viewprofile&amp;" . POST_USERS_URL . '=' . $auction_offer_rowset[$i]['user_id']) . '">' : '';
        $offerer .= $auction_offer_rowset[$i]['username'] ;
        $offerer .= ( $auction_offer_rowset[$i]['FK_auction_offer_user_id'] != ANONYMOUS ) ? '</a>' : '';
        $views = $auction_offer_rowset[$i]['auction_offer_views'];

        $sql = "SELECT COUNT(pic_id) AS total
		FROM " . AUCTION_IMAGE_TABLE . "
		WHERE pic_auction_id = $auction_offer_id";

	if( !($result = $db->sql_query($sql)) )
            {
              message_die(GENERAL_ERROR, 'Could not get pic count from auction information :: ' . $auction_offer_id, '', __LINE__, __FILE__, $sql);
            }

		if ( $row = $db->sql_fetchrow($result) )
		{
			$total_pics = ($row['total']) ? $row['total'] : 0;
		}
			else
		{
			$total_pics = 0;
		}

// here we should check if pic is featured and like ebay get the minithumb for the first 10 or 20 offers... not implemented yet
        if ($total_pics > 0)
           {
                 $auction_offer_picture = $images['icon_auction_pic'];
                 $auction_offer_picture_alt = $lang['auction_offer_picture_attached'] . ": " .$total_pics;
           }
        else
           {
                $auction_offer_picture = $images['icon_auction_no_pic'];
                $auction_offer_picture_alt = $lang['auction_offer_no_picture_attached'];
           }

        if ( ( $auction_offer_rowset[$i]['auction_offer_bold'] == 1) &&
             ( $auction_config_data['auction_offer_allow_bold'] == 1 ))
               {
                     $auction_offer_title = "<b>" . $auction_offer_title . "</b>";
               }
       if ( ( $auction_offer_rowset[$i]['auction_offer_special'] == 1) &&
            ( $auction_config_data['auction_offer_allow_special'] == 1 ))
               {
                       $template->assign_block_vars('offer_special', array(
                       'AUCTION_OFFER_OFFERER' => $offerer,
                       'AUCTION_OFFER_TIME_STOP' => create_date($board_config['default_dateformat'], $auction_offer_rowset[$i]['auction_offer_time_stop'], $board_config['board_timezone'])  . "</br>" . dateDiff(time(), $auction_offer_rowset[$i]['auction_offer_time_stop']),
                       'AUCTION_OFFER_TITLE' => $auction_offer_title,
                       'AUCTION_OFFER_VIEWS' => $views,
                       'AUCTION_OFFER_PICTURE' => $auction_offer_picture,
                       'AUCTION_OFFER_DIRECT_SELL' => ( $auction_offer_rowset[$i]['auction_offer_direct_sell_price'] > 0 ) ? "<img src=\"" . $images['direct_sell'] . "\" border=\"0\" />" : "",
                       'L_AUCTION_OFFER_PICTURE_ALT' => $auction_offer_picture_alt,
                       'AUCTION_OFFER_FIRST_PRICE' => $auction_offer_rowset[$i]['auction_offer_price_start']  . " " . $auction_config_data['currency'],
                       'AUCTION_OFFER_LAST_BID_PRICE' => ( $auction_offer_rowset[$i]['auction_offer_last_bid_price'] == 0 ) ? $lang['auction_no_bid'] : $auction_offer_rowset[$i]['auction_offer_last_bid_price'],
                       'AUCTION_OFFER_LAST_BID_USER' => ( $auction_offer_rowset[$i]['maxbidder_user_id'] == 0 ) ? $lang['auction_no_bidder'] : "<a href=\"" . append_sid("profile." . $phpEx . "?mode=viewprofile&amp;" . POST_USERS_URL . "=" . $auction_offer_rowset[$i]['maxbidder_user_id']) . "\">" . $auction_offer_rowset[$i]['maxbidder_user_name'] . "</a>",
                       'U_VIEW_AUCTION_OFFER' => $view_auction_offer_url));
               }
       elseif ( ( $auction_offer_rowset[$i]['auction_offer_on_top'] == 1) &&
                ( $auction_config_data['auction_offer_allow_on_top'] == 1 ))
               {
                      $template->assign_block_vars('offer_on_top', array(
                       'AUCTION_OFFER_OFFERER' => $offerer,
                       'AUCTION_OFFER_TIME_STOP' => create_date($board_config['default_dateformat'], $auction_offer_rowset[$i]['auction_offer_time_stop'], $board_config['board_timezone'])  . "</br>" . dateDiff(time(), $auction_offer_rowset[$i]['auction_offer_time_stop']),
                       'AUCTION_OFFER_TITLE' => $auction_offer_title,
                       'AUCTION_OFFER_VIEWS' => $views,
                       'AUCTION_OFFER_DIRECT_SELL' => ( $auction_offer_rowset[$i]['auction_offer_direct_sell_price'] > 0 ) ? "<img src=\"" . $images['direct_sell'] . "\" border=\"0\" />" : "",
                       'AUCTION_OFFER_PICTURE' => $auction_offer_picture,
                       'L_AUCTION_OFFER_PICTURE_ALT' => $auction_offer_picture_alt,
                       'AUCTION_OFFER_FIRST_PRICE' => $auction_offer_rowset[$i]['auction_offer_price_start']  . " " . $auction_config_data['currency'],
                       'AUCTION_OFFER_LAST_BID_PRICE' => ( $auction_offer_rowset[$i]['auction_offer_last_bid_price'] == 0 ) ? $lang['auction_no_bid'] : $auction_offer_rowset[$i]['auction_offer_last_bid_price'],
                       'AUCTION_OFFER_LAST_BID_USER' => ( $auction_offer_rowset[$i]['maxbidder_user_id'] == 0 ) ? $lang['auction_no_bidder'] : "<a href=\"" . append_sid("profile." . $phpEx . "?mode=viewprofile&amp;" . POST_USERS_URL . "=" . $auction_offer_rowset[$i]['maxbidder_user_id']) . "\">" . $auction_offer_rowset[$i]['maxbidder_user_name'] . "</a>",
                       'U_VIEW_AUCTION_OFFER' => $view_auction_offer_url));
               }
       else
                    {
                         $template->assign_block_vars('offer', array(
                              'AUCTION_OFFER_OFFERER' => $offerer,
                              'AUCTION_OFFER_TIME_STOP' => create_date($board_config['default_dateformat'], $auction_offer_rowset[$i]['auction_offer_time_stop'], $board_config['board_timezone'])  . "</br>" . dateDiff(time(), $auction_offer_rowset[$i]['auction_offer_time_stop']),
                              'AUCTION_OFFER_TITLE' => $auction_offer_title,
                              'AUCTION_OFFER_VIEWS' => $views,
                              'AUCTION_OFFER_DIRECT_SELL' => ( $auction_offer_rowset[$i]['auction_offer_direct_sell_price'] > 0 ) ? "<img src=\"" . $images['direct_sell'] . "\" border=\"0\" />" : "",
                              'AUCTION_OFFER_PICTURE' => $auction_offer_picture,
                              'L_AUCTION_OFFER_PICTURE_ALT' => $auction_offer_picture_alt,
                              'AUCTION_OFFER_FIRST_PRICE' => $auction_offer_rowset[$i]['auction_offer_price_start']  . " " . $auction_config_data['currency'],
                              'AUCTION_OFFER_LAST_BID_PRICE' => ( $auction_offer_rowset[$i]['auction_offer_last_bid_price'] == 0 ) ? $lang['auction_no_bid'] : $auction_offer_rowset[$i]['auction_offer_last_bid_price'],
                              'AUCTION_OFFER_LAST_BID_USER' => ( $auction_offer_rowset[$i]['maxbidder_user_id'] == 0 ) ? $lang['auction_no_bidder'] : "<a href=\"" . append_sid("profile." . $phpEx . "?mode=viewprofile&amp;" . POST_USERS_URL . "=" . $auction_offer_rowset[$i]['maxbidder_user_id']) . "\">" . $auction_offer_rowset[$i]['maxbidder_user_name'] . "</a>",
                              'U_VIEW_AUCTION_OFFER' => $view_auction_offer_url));
                    }
               }
          }
     else
          {
               // No topics
               $no_offer = ( $auction_room_row['auction_room_state'] == AUCTION_ROOM_LOCKED ) ? $lang['auction_room_locked'] : $lang['no_offer'];
               $template->assign_vars(array('L_NO_OFFER' => $no_offer));
               $template->assign_block_vars('no_offer', array() );
          }

     $template->pparse('body');
     include($phpbb_root_path . 'auction/auction_footer.'.$phpEx);
     include($phpbb_root_path . 'includes/page_tail.'.$phpEx);

?>