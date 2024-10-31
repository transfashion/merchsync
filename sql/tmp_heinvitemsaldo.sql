create table tmp_heinvitemsaldo (
	periode_id varchar(10) not null,
	periodemo_id varchar(6) not null,
	region_id varchar(5) not null, 
	branch_id varchar(7) not null, 
	heinv_id varchar(13) not null, 
	heinvitem_id varchar(13) not null, 
	end_qty int not null default 0,
	end_value decimal (15,2) not null default 0,
	cost decimal (15,2) not null default 0,
	PRIMARY KEY(periode_id, region_id, branch_id, heinvitem_id)
) engine=MyISAM COMMENT='Table Temporary untuk data item saldo';
