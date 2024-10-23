create table tmp_heinvsaldo (
	saldo_id varchar(30) not null,
	heinv_id varchar(13) not null, 
	C01 int not null default 0,
	C02 int not null default 0,
	C03 int not null default 0,
	C04 int not null default 0,
	C05 int not null default 0,

	C06 int not null default 0,
	C07 int not null default 0,
	C08 int not null default 0,
	C09 int not null default 0,
	C10 int not null default 0,

	C11 int not null default 0,
	C12 int not null default 0,
	C13 int not null default 0,
	C14 int not null default 0,
	C15 int not null default 0,

	C16 int not null default 0,
	C17 int not null default 0,
	C18 int not null default 0,
	C19 int not null default 0,
	C20 int not null default 0,

	C21 int not null default 0,
	C22 int not null default 0,
	C23 int not null default 0,
	C24 int not null default 0,
	C25 int not null default 0,

	saldodetil_endvalue decimal(15, 2) not null default 0,
	saldodetil_end int not null default 0,
	periode_id varchar(10) not null,
	region_id varchar(5) not null,
	PRIMARY key(saldo_id, heinv_id)
) engine=MyISAM COMMENT='Table Temporary untuk data item saldo';