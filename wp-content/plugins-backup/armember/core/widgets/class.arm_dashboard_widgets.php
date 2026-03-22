<?php 
if(!class_exists('armAdminDashboardWidgets'))
{
	class armAdminDashboardWidgets
	{
		function __construct()
		{
			add_filter('arm_load_arfhighchart_script',array($this,'arm_load_arfhighchart_script_func'));

			add_filter('arm_load_chart_container',array($this,'arm_load_chart_container_func'),10,2);

			add_filter('arm_widgets_global_css_section',array($this,'arm_widgets_global_css_section_func'),10,1 );

			add_filter('arm_get_all_members_without_administrator_dash',array($this,'arm_get_all_members_without_administrator_func'),10,2);

			add_filter( 'arm_admin_dashboard_manage_member_page_url', array($this,'arm_admin_dashboard_manage_member_page_url_func'), 10, 2 );
		}

		function arm_widgets_global_css_section_func($arm_global_css_section){     
			global $ARMember;    
			$arm_global_css_section = $ARMember->arm_set_global_css(false);
			return $arm_global_css_section; //phpcs:ignore
		}
	
		function arm_load_chart_container_func($arm_load_chart_container,$arm_chart_type){
			global $arm_members_class,$arm_subscription_plans;
			if($arm_chart_type == 'recent_members')
			{
				$arm_load_chart_container = '<div class="arm_members_chart_container arm_min_width_255" >';
				$arm_load_chart_container .= $arm_members_class->arm_chartRecentMembers();
				$arm_load_chart_container .= '<div class="armclear"></div>
						</div>
						<div class="armclear"></div>';
			}
	
			if($arm_chart_type == 'transactions')
			{
				$all_plans = $arm_subscription_plans->arm_get_all_subscription_plans();
				$arm_load_chart_container = '<div class="arm_members_chart_container arm_min_width_255" >';
				$arm_load_chart_container .= $arm_members_class->arm_chartPlanMembers($all_plans);
				$arm_load_chart_container .= '</div>
				<div class="armclear"></div>';
			}
	
			return $arm_load_chart_container;
		}
	
		function arm_load_arfhighchart_script_func(){
			if (!wp_script_is( 'arfhighcharts-script', 'enqueued' )) {
				wp_enqueue_script('arm_highchart', MEMBERSHIP_URL . '/js/highcharts.js', array('jquery'), MEMBERSHIP_VERSION);
			}
		}

		function arm_admin_dashboard_manage_member_page_url_func($arm_manage_member_page_url,$filter_type)
        {
            if($filter_type == 'inactive_member')
            {
                $arm_manage_member_page_url = $arm_manage_member_page_url . '&member_status_id=2';
            }
            if($filter_type == 'active_member'){
                $arm_manage_member_page_url = $arm_manage_member_page_url . '&member_status_id=1';
            }
            if($filter_type == 'pending_member')
            {
                $arm_manage_member_page_url = $arm_manage_member_page_url . '&member_status_id=3';
            }
            if($filter_type == 'terminated_member')
            {
                $arm_manage_member_page_url = $arm_manage_member_page_url . '&member_status_id=4';
            }
            return $arm_manage_member_page_url;
        }

        function arm_get_all_members_without_administrator_func($total_inactive_members,$inactive_type)
        {
			global $arm_members_class;
            $total_inactive_members = $arm_members_class->arm_get_all_members_without_administrator(0,1,0,$inactive_type);

            return $total_inactive_members;
        }

	}
	global $armAdminDashboardWidgets;
	$armAdminDashboardWidgets = new armAdminDashboardWidgets();
}