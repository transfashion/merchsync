create table tmp_heinv (
	heinv_id varchar(13) not null,
	heinv_art varchar(30),
	heinv_mat varchar(30),
	heinv_col varchar(30),
	heinv_name varchar(40),
	heinv_descr varchar(50),
	heinv_gtype varchar(10),
	heinv_isdisabled tinyint,
	heinv_isnonactive tinyint,
	heinv_iskonsinyasi tinyint,
	heinv_isassembly tinyint,
	heinv_priceori decimal(18,0),
	heinv_price01 decimal(18,0),
	heinv_pricedisc01 decimal(18,0),
	heinv_produk varchar(50),
	heinv_bahan varchar(70),
	heinv_pemeliharaan varchar(100),
	heinv_logo varchar(30),
	heinv_dibuatdi varchar(30),
	heinv_other1 varchar(50),
	heinv_other2 varchar(50),
	heinv_other3 varchar(50),
	heinv_other4 varchar(50),
	heinv_other5 varchar(50),
	heinv_lastrvid varchar(30),
	heinv_lastrvdate date,
	heinv_lastrvqty int,
	heinv_lastpriceid varchar(30),
	heinv_lastpricedate date,
	heinv_lastcost decimal(18,2),
	heinv_lastcostid varchar(30),
	heinv_lastcostdate date,
	heinvgro_id varchar(10),
	heinvctg_id varchar(10),
	heinv_group1 varchar(60),
	heinv_group2 varchar(60),
	heinv_gender varchar(1),
	heinv_color1 varchar(30),
	heinv_color2 varchar(30),
	heinv_color3 varchar(30),
	heinv_hscode_ship bigint,
	heinv_plbname varchar(100),
	ref_id varchar(30),
	season_id varchar(10),
	region_id varchar(5),
	invcls_id varchar(8),
	heinv_isweb tinyint,
	heinv_weight decimal(5,2),
	heinv_length decimal(5,2),
	heinv_width decimal(5,2),
	heinv_height decimal(5,2),
	heinv_webdescr varchar(2500),
	deftype_id varchar(10),
	ref_heinv_id varchar(13),
	ref_heinvitem_id varchar(13),
	PRIMARY key (heinv_id)
);