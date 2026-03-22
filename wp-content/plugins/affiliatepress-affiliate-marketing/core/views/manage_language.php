<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
(function (global, factory) {
    typeof exports === 'object' && typeof module !== 'undefined' ? module.exports = factory() :
    typeof define === 'function' && define.amd ? define(factory) :
    (global = typeof globalThis !== 'undefined' ? globalThis : global || self, global.ElementPlusLocaleData = factory());
  })(this, (function () { 'use strict';
    var en = {
      name: "en",
      el: {
        breadcrumb: {
          label: "<?php esc_html_e('Breadcrumb', 'affiliatepress-affiliate-marketing'); ?>"
        },
        colorpicker: {
          confirm: "<?php esc_html_e('OK', 'affiliatepress-affiliate-marketing'); ?>",
          clear: "<?php esc_html_e('Clear', 'affiliatepress-affiliate-marketing'); ?>",
          defaultLabel: "<?php esc_html_e('color picker', 'affiliatepress-affiliate-marketing'); ?>",
          description: "<?php esc_html_e('current color is {color}. press enter to select a new color.', 'affiliatepress-affiliate-marketing'); ?>"
        },
        datepicker: {
          now: "<?php esc_html_e('Now', 'affiliatepress-affiliate-marketing'); ?>",
          today: "<?php esc_html_e('Today', 'affiliatepress-affiliate-marketing'); ?>",
          cancel: "<?php esc_html_e('Cancel', 'affiliatepress-affiliate-marketing'); ?>",
          clear: "<?php esc_html_e('Clear', 'affiliatepress-affiliate-marketing'); ?>",
          confirm: "<?php esc_html_e('OK', 'affiliatepress-affiliate-marketing'); ?>",
          dateTablePrompt: "<?php esc_html_e('Use the arrow keys and enter to select the day of the month', 'affiliatepress-affiliate-marketing'); ?>",
          monthTablePrompt: "<?php esc_html_e('Use the arrow keys and enter to select the month', 'affiliatepress-affiliate-marketing'); ?>",
          yearTablePrompt: "<?php esc_html_e('Use the arrow keys and enter to select the year', 'affiliatepress-affiliate-marketing'); ?>",
          selectedDate: "<?php esc_html_e('Selected date', 'affiliatepress-affiliate-marketing'); ?>",
          selectDate: "<?php esc_html_e('Select date', 'affiliatepress-affiliate-marketing'); ?>",
          selectTime: "<?php esc_html_e('Select time', 'affiliatepress-affiliate-marketing'); ?>",
          startDate: "<?php esc_html_e('Start Date', 'affiliatepress-affiliate-marketing'); ?>",
          startTime: "<?php esc_html_e('Start Time', 'affiliatepress-affiliate-marketing'); ?>",
          endDate: "<?php esc_html_e('End Date', 'affiliatepress-affiliate-marketing'); ?>",
          endTime: "<?php esc_html_e('End Time', 'affiliatepress-affiliate-marketing'); ?>",
          prevYear: "<?php esc_html_e('Previous Year', 'affiliatepress-affiliate-marketing'); ?>",
          nextYear: "<?php esc_html_e('Next Year', 'affiliatepress-affiliate-marketing'); ?>",
          prevMonth: "<?php esc_html_e('Previous Month', 'affiliatepress-affiliate-marketing'); ?>",
          nextMonth: "<?php esc_html_e('Next Month', 'affiliatepress-affiliate-marketing'); ?>",
          year: "",
          month1: "<?php esc_html_e('January', 'affiliatepress-affiliate-marketing'); ?>",
          month2: "<?php esc_html_e('February', 'affiliatepress-affiliate-marketing'); ?>",
          month3: "<?php esc_html_e('March', 'affiliatepress-affiliate-marketing'); ?>",
          month4: "<?php esc_html_e('April', 'affiliatepress-affiliate-marketing'); ?>",
          month5: "<?php esc_html_e('May', 'affiliatepress-affiliate-marketing'); ?>",
          month6: "<?php esc_html_e('June', 'affiliatepress-affiliate-marketing'); ?>",
          month7: "<?php esc_html_e('July', 'affiliatepress-affiliate-marketing'); ?>",
          month8: "<?php esc_html_e('August', 'affiliatepress-affiliate-marketing'); ?>",
          month9: "<?php esc_html_e('September', 'affiliatepress-affiliate-marketing'); ?>",
          month10: "<?php esc_html_e('October', 'affiliatepress-affiliate-marketing'); ?>",
          month11: "<?php esc_html_e('November', 'affiliatepress-affiliate-marketing'); ?>",
          month12: "<?php esc_html_e('December', 'affiliatepress-affiliate-marketing'); ?>",
          week: "<?php esc_html_e('week', 'affiliatepress-affiliate-marketing'); ?>",
          weeks: {
            sun: "<?php esc_html_e('Sun', 'affiliatepress-affiliate-marketing'); ?>",
            mon: "<?php esc_html_e('Mon', 'affiliatepress-affiliate-marketing'); ?>",
            tue: "<?php esc_html_e('Tue', 'affiliatepress-affiliate-marketing'); ?>",
            wed: "<?php esc_html_e('Wed', 'affiliatepress-affiliate-marketing'); ?>",
            thu: "<?php esc_html_e('Thu', 'affiliatepress-affiliate-marketing'); ?>",
            fri: "<?php esc_html_e('Fri', 'affiliatepress-affiliate-marketing'); ?>",
            sat: "<?php esc_html_e('Sat', 'affiliatepress-affiliate-marketing'); ?>"
          },
          weeksFull: {
            sun: "<?php esc_html_e('Sunday', 'affiliatepress-affiliate-marketing'); ?>",
            mon: "<?php esc_html_e('Monday', 'affiliatepress-affiliate-marketing'); ?>",
            tue: "<?php esc_html_e('Tuesday', 'affiliatepress-affiliate-marketing'); ?>",
            wed: "<?php esc_html_e('Wednesday', 'affiliatepress-affiliate-marketing'); ?>",
            thu: "<?php esc_html_e('Thursday', 'affiliatepress-affiliate-marketing'); ?>",
            fri: "<?php esc_html_e('Friday', 'affiliatepress-affiliate-marketing'); ?>",
            sat: "<?php esc_html_e('Saturday', 'affiliatepress-affiliate-marketing'); ?>"
          },
          months: {
            jan: "<?php esc_html_e('Jan', 'affiliatepress-affiliate-marketing'); ?>",
            feb: "<?php esc_html_e('Feb', 'affiliatepress-affiliate-marketing'); ?>",
            mar: "<?php esc_html_e('Mar', 'affiliatepress-affiliate-marketing'); ?>",
            apr: "<?php esc_html_e('Apr', 'affiliatepress-affiliate-marketing'); ?>",
            may: "<?php esc_html_e('May', 'affiliatepress-affiliate-marketing'); ?>",
            jun: "<?php esc_html_e('Jun', 'affiliatepress-affiliate-marketing'); ?>",
            jul: "<?php esc_html_e('Jul', 'affiliatepress-affiliate-marketing'); ?>",
            aug: "<?php esc_html_e('Aug', 'affiliatepress-affiliate-marketing'); ?>",
            sep: "<?php esc_html_e('Sep', 'affiliatepress-affiliate-marketing'); ?>",
            oct: "<?php esc_html_e('Oct', 'affiliatepress-affiliate-marketing'); ?>",
            nov: "<?php esc_html_e('Nov', 'affiliatepress-affiliate-marketing'); ?>",
            dec: "<?php esc_html_e('Dec', 'affiliatepress-affiliate-marketing'); ?>"
          }
        },
        inputNumber: {
          decrease: "<?php esc_html_e('decrease number', 'affiliatepress-affiliate-marketing'); ?>",
          increase: "<?php esc_html_e('increase number', 'affiliatepress-affiliate-marketing'); ?>"
        },
        select: {
          loading: "<?php esc_html_e('Loading', 'affiliatepress-affiliate-marketing'); ?>",
          noMatch: "<?php esc_html_e('No matching data', 'affiliatepress-affiliate-marketing'); ?>",
          noData: "<?php esc_html_e('No data', 'affiliatepress-affiliate-marketing'); ?>",
          placeholder: "<?php esc_html_e('Select', 'affiliatepress-affiliate-marketing'); ?>"
        },
        dropdown: {
          toggleDropdown: "<?php esc_html_e('Toggle Dropdown', 'affiliatepress-affiliate-marketing'); ?>"
        },
        cascader: {
          noMatch: "<?php esc_html_e('No matching data', 'affiliatepress-affiliate-marketing'); ?>",
          loading: "<?php esc_html_e('Loading', 'affiliatepress-affiliate-marketing'); ?>",
          placeholder: "<?php esc_html_e('Select', 'affiliatepress-affiliate-marketing'); ?>",
          noData: "<?php esc_html_e('No data', 'affiliatepress-affiliate-marketing'); ?>"
        },
        pagination: {
          goto: "<?php esc_html_e('Go to', 'affiliatepress-affiliate-marketing'); ?>",
          pagesize: "/page",
          total: "<?php esc_html_e('Total', 'affiliatepress-affiliate-marketing'); ?> {total}",
          pageClassifier: "",
          page: "<?php esc_html_e('Page', 'affiliatepress-affiliate-marketing'); ?>",
          prev: "<?php esc_html_e('Go to previous page', 'affiliatepress-affiliate-marketing'); ?>",
          next: "<?php esc_html_e('Go to next page', 'affiliatepress-affiliate-marketing'); ?>",
          currentPage: "<?php esc_html_e('page', 'affiliatepress-affiliate-marketing'); ?> {pager}",
          prevPages: "<?php esc_html_e('Previous', 'affiliatepress-affiliate-marketing'); ?> {pager} <?php esc_html_e('pages', 'affiliatepress-affiliate-marketing'); ?>",
          nextPages: "<?php esc_html_e('Next', 'affiliatepress-affiliate-marketing'); ?> {pager} <?php esc_html_e('pages', 'affiliatepress-affiliate-marketing'); ?>",
          deprecationWarning: "<?php esc_html_e('Deprecated usages detected, please refer to the el-pagination documentation for more details', 'affiliatepress-affiliate-marketing'); ?>"
        },
        dialog: {
          close: "<?php esc_html_e('Close this dialog', 'affiliatepress-affiliate-marketing'); ?>"
        },
        drawer: {
          close: "<?php esc_html_e('Close this dialog', 'affiliatepress-affiliate-marketing'); ?>"
        },
        messagebox: {
          title: "<?php esc_html_e('Message', 'affiliatepress-affiliate-marketing'); ?>",
          confirm: "<?php esc_html_e('OK', 'affiliatepress-affiliate-marketing'); ?>",
          cancel: "<?php esc_html_e('Cancel', 'affiliatepress-affiliate-marketing'); ?>",
          error: "<?php esc_html_e('Illegal input', 'affiliatepress-affiliate-marketing'); ?>",
          close: "<?php esc_html_e('Close this dialog', 'affiliatepress-affiliate-marketing'); ?>"
        },
        upload: {
          deleteTip: "<?php esc_html_e('press delete to remove', 'affiliatepress-affiliate-marketing'); ?>",
          delete: "<?php esc_html_e('Delete', 'affiliatepress-affiliate-marketing'); ?>",
          preview: "<?php esc_html_e('Preview', 'affiliatepress-affiliate-marketing'); ?>",
          continue: "<?php esc_html_e('Continue', 'affiliatepress-affiliate-marketing'); ?>"
        },
        slider: {
          defaultLabel: "<?php esc_html_e('slider between', 'affiliatepress-affiliate-marketing'); ?> {min} <?php esc_html_e('and', 'affiliatepress-affiliate-marketing'); ?> {max}",
          defaultRangeStartLabel: "<?php esc_html_e('pick start value', 'affiliatepress-affiliate-marketing'); ?>",
          defaultRangeEndLabel: "<?php esc_html_e('pick end value', 'affiliatepress-affiliate-marketing'); ?>"
        },
        table: {
          emptyText: "<?php esc_html_e('No Data', 'affiliatepress-affiliate-marketing'); ?>",
          confirmFilter: "<?php esc_html_e('Confirm', 'affiliatepress-affiliate-marketing'); ?>",
          resetFilter: "<?php esc_html_e('Reset', 'affiliatepress-affiliate-marketing'); ?>",
          clearFilter: "<?php esc_html_e('All', 'affiliatepress-affiliate-marketing'); ?>",
          sumText: "<?php esc_html_e('Sum', 'affiliatepress-affiliate-marketing'); ?>"
        },
        tour: {
          next: "<?php esc_html_e('Next', 'affiliatepress-affiliate-marketing'); ?>",
          previous: "<?php esc_html_e('Previous', 'affiliatepress-affiliate-marketing'); ?>",
          finish: "<?php esc_html_e('Finish', 'affiliatepress-affiliate-marketing'); ?>"
        },
        tree: {
          emptyText: "<?php esc_html_e('No Data', 'affiliatepress-affiliate-marketing'); ?>"
        },
        transfer: {
          noMatch: "<?php esc_html_e('No matching data', 'affiliatepress-affiliate-marketing'); ?>",
          noData: "<?php esc_html_e('No data', 'affiliatepress-affiliate-marketing'); ?>",
          titles: ["<?php esc_html_e('List 1', 'affiliatepress-affiliate-marketing'); ?>", "<?php esc_html_e('List 2', 'affiliatepress-affiliate-marketing'); ?>"],
          filterPlaceholder: "<?php esc_html_e('Enter keyword', 'affiliatepress-affiliate-marketing'); ?>",
          noCheckedFormat: "{total} <?php esc_html_e('items', 'affiliatepress-affiliate-marketing'); ?>",
          hasCheckedFormat: "{checked}/{total} <?php esc_html_e('checked', 'affiliatepress-affiliate-marketing'); ?>"
        },
        image: {
          error: "<?php esc_html_e('FAILED', 'affiliatepress-affiliate-marketing'); ?>"
        },
        pageHeader: {
          title: "<?php esc_html_e('Back', 'affiliatepress-affiliate-marketing'); ?>"
        },
        popconfirm: {
          confirmButtonText: "<?php esc_html_e('Yes', 'affiliatepress-affiliate-marketing'); ?>",
          cancelButtonText: "<?php esc_html_e('No', 'affiliatepress-affiliate-marketing'); ?>"
        },
        carousel: {
          leftArrow: "<?php esc_html_e('Carousel arrow left', 'affiliatepress-affiliate-marketing'); ?>",
          rightArrow: "<?php esc_html_e('Carousel arrow right', 'affiliatepress-affiliate-marketing'); ?>",
          indicator: "<?php esc_html_e('Carousel switch to index', 'affiliatepress-affiliate-marketing'); ?> {index}"
        }
      }
    };
  
    return en;
  
  }));
