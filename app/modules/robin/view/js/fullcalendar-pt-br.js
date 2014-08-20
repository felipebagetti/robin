(function(e) {
	"function" == typeof define && define.amd
			? define(["jquery", "moment"], e)
			: e(jQuery, moment)
})(function(e, t) {
	t.lang("pt-br", {
		months : "janeiro_fevereiro_mar�o_abril_maio_junho_julho_agosto_setembro_outubro_novembro_dezembro"
				.split("_"),
		monthsShort : "jan_fev_mar_abr_mai_jun_jul_ago_set_out_nov_dez"
				.split("_"),
		weekdays : "domingo_segunda-feira_ter�a-feira_quarta-feira_quinta-feira_sexta-feira_s�bado"
				.split("_"),
		weekdaysShort : "dom_seg_ter_qua_qui_sex_s�b".split("_"),
		weekdaysMin : "dom_2�_3�_4�_5�_6�_s�b".split("_"),
		longDateFormat : {
			LT : "HH:mm",
			L : "DD/MM/YYYY",
			LL : "D [de] MMMM [de] YYYY",
			LLL : "D [de] MMMM [de] YYYY [�s] LT",
			LLLL : "dddd, D [de] MMMM [de] YYYY [�s] LT"
		},
		calendar : {
			sameDay : "[Hoje �s] LT",
			nextDay : "[Amanh� �s] LT",
			nextWeek : "dddd [�s] LT",
			lastDay : "[Ontem �s] LT",
			lastWeek : function() {
				return 0 === this.day() || 6 === this.day()
						? "[�ltimo] dddd [�s] LT"
						: "[�ltima] dddd [�s] LT"
			},
			sameElse : "L"
		},
		relativeTime : {
			future : "em %s",
			past : "%s atr�s",
			s : "segundos",
			m : "um minuto",
			mm : "%d minutos",
			h : "uma hora",
			hh : "%d horas",
			d : "um dia",
			dd : "%d dias",
			M : "um m�s",
			MM : "%d meses",
			y : "um ano",
			yy : "%d anos"
		},
		ordinal : "%d�"
	}), e.fullCalendar.datepickerLang("pt-br", "pt-BR", {
				closeText : "Fechar",
				prevText : "&#x3C;Anterior",
				nextText : "Pr�ximo&#x3E;",
				currentText : "Hoje",
				monthNames : ["Janeiro", "Fevereiro", "Mar�o", "Abril", "Maio",
						"Junho", "Julho", "Agosto", "Setembro", "Outubro",
						"Novembro", "Dezembro"],
				monthNamesShort : ["Jan", "Fev", "Mar", "Abr", "Mai", "Jun",
						"Jul", "Ago", "Set", "Out", "Nov", "Dez"],
				dayNames : ["Domingo", "Segunda-feira", "Ter�a-feira",
						"Quarta-feira", "Quinta-feira", "Sexta-feira", "S�bado"],
				dayNamesShort : ["Dom", "Seg", "Ter", "Qua", "Qui", "Sex",
						"S�b"],
				dayNamesMin : ["Dom", "Seg", "Ter", "Qua", "Qui", "Sex", "S�b"],
				weekHeader : "Sm",
				dateFormat : "dd/mm/yy",
				firstDay : 0,
				isRTL : !1,
				showMonthAfterYear : !1,
				yearSuffix : ""
			}), e.fullCalendar.lang("pt-br", {
				defaultButtonText : {
					month : "M�s",
					week : "Semana",
					day : "Dia",
					list : "Compromissos"
				},
				allDayText : "dia inteiro"
			})
});