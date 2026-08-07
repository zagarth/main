       IDENTIFICATION DIVISION.
       PROGRAM-ID. OE27.                                                        
      ***
      ***  WRITE QUOTATION
      ***  CADMAN MANUFACTURING
      ***  21-SEP-95 - DISCOUNT PRICES FOR "CHM" ACCOUNTS AS PER AR31
      ***  23-NOV-95 - ADD LOGIC FOR SY-MRKT-MKUP
      ***  28-AMY-96 - ADD PST ACCOUNT 30408
      ***  18-JUN-96 - ADD LOGIC FOR GCL720 GCL721
      ***  19-AUG-96 - ADD LOGIC FOR GCL722 & TERMS CODES 'R' & 'L'
      ***  16-OCT-96 - CHANGE GCL722 TO GCL730
      ***  01-APR-97 - ADD LOGIC FOR HST CHANGES
      ***  30-MAR-98 - CHANGE GCL720 LOGIC CHECK TO 50.00
      ***  30-MAR-01 - CHANGE GCL720 LOGIC CHECK TO 100.00
      ***
       ENVIRONMENT DIVISION.
       INPUT-OUTPUT SECTION.
       FILE-CONTROL.
           SELECT SY-FILE ASSIGN TO WS-SY-FILE-ID
             ORGANIZATION INDEXED
             ACCESS DYNAMIC
             LOCK MODE IS MANUAL
             RECORD KEY IS SY-1-KEY
             FILE STATUS IS LK-IO-STAT.
           SELECT GL-FILE ASSIGN TO WS-GL-FILE-ID
             ORGANIZATION INDEXED
             ACCESS DYNAMIC
             LOCK MODE IS MANUAL
             RECORD KEY IS GL-1-KEY
             FILE STATUS IS LK-IO-STAT.
           SELECT AR-FILE ASSIGN TO WS-AR-FILE-ID
             ORGANIZATION INDEXED
             ACCESS DYNAMIC
             LOCK MODE IS MANUAL
             RECORD KEY IS AR-1-KEY
             FILE STATUS IS LK-IO-STAT.
           SELECT IP-FILE ASSIGN TO WS-IP-FILE-ID
             ACCESS MODE IS DYNAMIC
             LOCK MODE IS MANUAL
             ORGANIZATION INDEXED
             RECORD KEY IS IP-KEY
             FILE STATUS IS LK-IO-STAT.
           SELECT PRNT-FILE ASSIGN TO "$SPOOL/OE27".
      ****************
       DATA DIVISION.
      ****************
       FILE SECTION.
       COPY SY.
       COPY AR.
       COPY GL.
       COPY IP.
       FD PRNT-FILE.
       01  PRNT-RCRD               PIC X(80).
      ****************
       WORKING-STORAGE SECTION.
       01  PR-COMMAND.
           03  PR-CMD       PIC X(40) VALUE
           "lp -d$DEST -s $SPOOL/OE27" & x"00".
      ****************
       01  WS-SY-FILE-ID.
           02  FILLER              PIC X(2) VALUE 'SY'.
           02  WS-SY-COMP          PIC XX.
           02  FILLER              PIC X(4) VALUE '.DAT'.
       01  WS-GL-FILE-ID.
           02  FILLER              PIC X(2) VALUE 'GL'.
           02  WS-GL-COMP          PIC XX.
           02  FILLER              PIC X(4) VALUE '.DAT'.
       01  WS-AR-FILE-ID.
           02  FILLER              PIC X(2) VALUE 'AR'.
           02  WS-AR-COMP          PIC XX.
           02  FILLER              PIC X(4) VALUE '.DAT'.
       01  WS-IP-FILE-ID.
           02  FILLER              PIC X(2) VALUE 'IP'.
           02  WS-IP-COMP          PIC XX.
           02  FILLER              PIC XXXX VALUE '.DAT'.
        01  PRNT-OPEN              PIC X VALUE 'N'.
      *
       01  SC-DATE.
           02  SC-DD               PIC XX.
           02  SC-DATE-D1          PIC X.
           02  SC-MMM 	PIC XXX.
           02  SC-DATE-D2          PIC X.
           02  SC-YY               PIC XX.
       01  SC-LINE.
           02  SC-ITEM             PIC X(15).
           02  SC-ITEM-NMBR REDEFINES SC-ITEM.
             03  SC-ITEM-N         PIC X OCCURS 15.
           02  SC-DESC.
             03  SC-DSC-1A           PIC X(8).
             03  SC-DSC-1B           PIC X(16).
           02  SC-CODE             PIC X(5).
           02  SC-CATG             PIC XXX.
           02  SC-ORDR-QTY         PIC S9(3).
           02  SC-SHIP-QTY         PIC S9(3).
       01  SC-LINE-2.
           02  SC-PRICE            PIC S9(4)V99.
           02  SC-EXTN             PIC S9(5)V99.
           02  SC-ITEM-2.
             03  SC-ITEM-2A        PIC X(5).
             03  FILLER            PIC X.
             03  SC-ITEM-2B        PIC XX.
             03  FILLER            PIC X(6).
           02  SC-DESC-2           PIC X(24).
           02  SC-PRICE-2          PIC S9(4)V99.
           02  SC-ITEM-PR          PIC X(15).
           02  SC-DESC-PR          PIC X(24).
	01  SC-DATA.
           02  SC-BY               PIC XX.
           02  SC-SHIP-CUST.
             03  SC-SHIP-CUST-HI   PIC XXX.
             03  FILLER            PIC XXX.
           02  SC-BILL-CUST        PIC X(6).
           02  SC-GST-STAT         PIC X(8).
           02  SC-PST-LIC          PIC X(8).
       01  SC-TERMS                PIC X.
       01  SV-DATE                 PIC X(9).
       01  SC-INV                  PIC X.
       01  SC-GOLD-PRCE            PIC 999.
	01  SC-ADDT-ORDR	PIC X(6).
      *
       01  WK-PRCE                 PIC 9(4)V99.
       01  WK-PRCE-2 REDEFINES WK-PRCE.
           02  WK-DLLR             PIC 9999.
           02  WK-CENT             PIC 99.
      *
       01  WK-INVC-DATE.
		02  WK-INVC-CN	PIC 99.
		02  WK-INVC-YYMMDD.
             03  WK-INVC-YY        PIC 99.
             03  WK-INVC-MM        PIC 99.
             03  WK-INVC-DD        PIC 99.
       01  WK-FRWD-DATE.
		02  WK-FRWD-CN	PIC 99.
		02  WK-FRWD-YYMMDD.
             03  WK-FRWD-YY          PIC 99.
             03  WK-FRWD-MM          PIC 99.
             03  WK-FRWD-DD          PIC 99.
       01  WK-LINE-EXTN            PIC S9(6)V99 VALUE ZEROS.
       01  WK-EXTN                 PIC S9(6)V99 VALUE ZEROS.
       01  WK-TOTL-PRICE           PIC S9(4)V99 VALUE ZEROS.
       01  WK-TOTL-AMNT            PIC S9(6)V99 VALUE ZEROS.
       01  WK-MRKT-MKUP            PIC 99V99.
--->   01  ST-MDSE-DISC            PIC S9(4)V99.
--->   01  ST-DISC-RATE            PIC 99V99.
      *
       01  DS-LINES.
           02  DS-LINE-0           PIC X(78).
           02  DS-LINE-1           PIC X(78).
           02  DS-LINE-2           PIC X(78).
           02  DS-LINE-3           PIC X(78).
           02  DS-LINE-4           PIC X(78).
           02  DS-LINE-5           PIC X(78).
           02  DS-LINE-6.
             03  DS-ITEM           PIC X(15).
             03  DS-ITEM-NMBR REDEFINES DS-ITEM.
               04  DS-ITEM-N       PIC X OCCURS 15.
             03  FILLER            PIC X.
             03  DS-DESC.
               04  DS-DSC-1        PIC X(8).
               04  DS-DSC-2        PIC X(5).
               04  FILLER          PIC X.
               04  DS-DSC-LNTH     PIC XX.
               04  FILLER          PIC X.
               04  DS-DSC-CM       PIC XX.
               04  FILLER          PIC X.
               04  DS-DSC-BLK      PIC XXXX.
             03  FILLER            PIC X.
             03  DS-CODE           PIC X(5).
             03  FILLER            PIC X.
             03  DS-ORDR-QTY       PIC ZZZ-.
             03  FILLER            PIC XX.
             03  DS-SHIP-QTY       PIC ZZZ-.
             03  FILLER            PIC X.
             03  DS-PRICE          PIC Z,ZZZ.ZZ.
             03  FILLER            PIC XX.
             03  DS-EXT            PIC ZZ,ZZZ.ZZ-.
      *
       01  ST-LINE-TABLE.
           02  ST-LINE-TAB OCCURS 80.
             03  ST-LINE-ITEM-1    PIC X(15).
             03  ST-LINE-DESC      PIC X(24).
             03  ST-LINE-CODE      PIC X(5).
             03  ST-LINE-CATG      PIC XXX.
             03  ST-LINE-ORDR-QTY  PIC S9(3).
             03  ST-LINE-SHIP-QTY  PIC S9(3).
       01  ST-LINE-TABLE-2.
           02  ST-LINE-TAB-2 OCCURS 80.
             03  ST-LINE-PRICE-1   PIC S9(4)V99.
             03  ST-LINE-EXT       PIC S9(5)V99.
             03  ST-LINE-ITEM-2    PIC X(15).
             03  ST-LINE-DESC-2    PIC X(24).
             03  ST-LINE-PRICE-2   PIC S9(4)V99.
             03  ST-LINE-ITEM-PR   PIC X(15).
             03  ST-LINE-DESC-PR   PIC X(24).
       01  S1                     PIC S99.
       01  S2                     PIC S99.
       01  ST-TOTLS.
           02  ST-SUB-TOTL         PIC S9(6)V99.
           02  ST-TAX-BASE PIC S9(6)V99.
           02  ST-EXTN             PIC S9(6)V99.
           02  ST-EXTN-2           PIC S9(6)V99.
           02  ST-GL-AR            PIC S9(7)V99.
       01  ST-INVC-NMBR            PIC X(6).
       01  ST-SALE-AMNT        PIC S9(6)V99.
       01  ST-CASH-DISC            PIC S9(4)V99.
       01  ST-PST                  PIC S9(7)V99.
      *
       01  ITEM-CTR                PIC 99 VALUE ZEROS.
       01  DS-ITEM-CTR             PIC 99 VALUE ZEROS.
       01  LINE-CTR                PIC 99 VALUE ZEROS.
       01  LINE-LMT                PIC 99 VALUE 28.
       01  BLANKLINE               PIC XX VALUE SPACES.
      *
       01  WS-SCREEN.
           02  WS-FUNC-1           PIC X(09).
           02  WS-FUNC-2           PIC X(12).
           02  WS-FUNC-3           PIC X(11).
           02  WS-FUNC-4           PIC X(11).
           02  WS-FUNC-5           PIC X(12).
      *
      ***           PRINT LINES
      *
       01  PR-LINES.
           02  PR-LINE.
             03  FILLER            PIC X(73).
             03  PR-INVC-NMBR      PIC X(6).
           02  PR-LINE-1.
             03  FILLER            PIC X(7).
             03  PR-NAME           PIC X(30).
             03  FILLER            PIC X(11).
             03  PR-SHIP-NAME      PIC X(30).
           02  PR-LINE-2.
             03  FILLER            PIC X(7).
             03  PR-ADDR-2         PIC X(30).
             03  FILLER            PIC X(11).
             03  PR-SHIP-ADDR-2    PIC X(30).
           02  PR-LINE-3.
             03  FILLER            PIC X(7).
             03  PR-ADDR-3         PIC X(30).
             03  FILLER            PIC X(11).
             03  PR-SHIP-ADDR-3    PIC X(30).
           02  PR-LINE-4.
             03  FILLER            PIC X(7).
             03  PR-ADDR-4.
		    04  PR-ADDR-PROV	PIC X(23).
		    04  PR-ADDR-PST-CDE	PIC X(7).
             03  FILLER            PIC X(11).
             03  PR-SHIP-ADDR-4.
		    04  PR-SHIP-PROV	PIC X(23).
		    04  PR-SHIP-PST-CDE	PIC X(7).
		02  PR-LINE-5.
		  03  FILLER	PIC X(80).
           02  PR-LINE-6.
             03  PR-CUST           PIC X(6).
             03  FILLER            PIC XXXX.
             03  PR-SLSM           PIC XX.
             03  FILLER            PIC X(3).
             03  PR-ORDR-NMBR      PIC X(6).
             03  FILLER            PIC X(4).
             03  PR-SHIP-VIA       PIC X(15).
             03  FILLER            PIC XX.
             03  PR-SHIP-DATE      PIC X(9).
             03  FILLER            PIC X.
             03  PR-TERMS          PIC X(16).
             03  FILLER            PIC X.
             03  PR-INVC-DATE      PIC X(9).
             03  FILLER            PIC X.
             03  PR-PAGE           PIC 9.
           02  PR-LINE-8.
             03  PR-ORDR-QTY       PIC ZZZ-.
             03  FILLER            PIC X(6).
             03  PR-SHIP-QTY       PIC ZZZ-.
             03  FILLER            PIC XXXX.
             03  PR-ITEM           PIC X(15).
             03  FILLER            PIC X.
             03  PR-DESC           PIC X(24).
		  03  FILLER            PIC XXX.
             03  PR-PRICE          PIC Z,ZZZ.ZZ.
             03  FILLER            PIC X.
             03  PR-EXT            PIC ZZ,ZZZ.ZZ-.
       01  PR-LINE-A.
           02  FILLER              PIC X(29) VALUE SPACES.
           02  FILLER              PIC X(23) VALUE
             'CONTINUED ON NEXT PAGE'.
	01  PR-DISC.
		02  FILLER	PIC X(18) VALUE SPACES.
		02  FILLER	PIC X(15) VALUE
		  'YOU MAY DEDUCT '.
		02  PR-DISC-AMNT	PIC $*,***.99.
		02  FILLER	PIC X(12) VALUE ' IF PAID BY '.
		02  PR-DISC-DD	PIC XX.
		02  FILLER	PIC X VALUE '-'.
		02  PR-DISC-MMM	PIC XXX.
		02  FILLER	PIC X VALUE '-'.
		02  PR-DISC-YY	PIC XX.
	01  PR-TERM-S.
		02  FILLER	PIC X(19) VALUE SPACES.
		02  FILLER	PIC X(17) VALUE
		  'FIRST PAYMENT OF '.
		02  PR-TERM-S-AMNT	PIC $*,***.99.
		02  FILLER	PIC X(8) VALUE ' IS DUE '.
		02  PR-TERM-S-DD	PIC XX.
		02  FILLER	PIC X VALUE '-'.
		02  PR-TERM-S-MMM	PIC XXX.
		02  FILLER	PIC X VALUE '-'.
		02  PR-TERM-S-YY	PIC XX.
	01  PR-TERM-CJG.
		02  FILLER	PIC X(15) VALUE SPACES.
		02  FILLER	PIC X(49) VALUE
		  'PLAN DISCOUNT ONLY AVAILABLE ON SUB-TOTAL AMOUNT'.
       COPY SC.
	01  SCRN-FUNC-LINE.
		02  LINE 02 COLUMN 02 PIC X(55) FROM WS-SCREEN
		  FOREGROUND-COLOR 6.
		02  LINE 02 COLUMN 58 PIC X(9) FROM SC-PERD
		  FOREGROUND-COLOR 6.
		02  LINE 02 COLUMN 68 PIC ZZZ,ZZZ.99CR
		  FROM SC-HEAD-TOTL FOREGROUND-COLOR 6.
       01  SCRN-PERD.
           02  LINE 7 COLUMN 26 VALUE 'ACCOUNTING PERIOD IS'.
           02  LINE 7 COLUMN 47 PIC X(9) FROM SC-PERD.
           02  LINE 9 COLUMN 23 VALUE
             'DO YOU WANT THE NEXT PERIOD Y/N ?'.
           02  LINE 9 COLUMN 58 PIC X TO SC-Y-N AUTO.
       01  SCRN-INVC-DATE.
           02  LINE 11 COLUMN 28 VALUE 'INVOICE DATE IS'.
           02  LINE 11 COLUMN 44 PIC X(9) USING SC-DATE.
       01  SCRN-GOLD.
           02  LINE 16 COLUMN 31 VALUE 'PRICE OF GOLD IS'.
           02  LINE 16 COLUMN 48 PIC ZZ9 USING SC-GOLD-PRCE.
      *
      ***             SCREEN TWO - INVOICE ENTRY SCREEN
      *
       01  SCRN-MASK.
           02  LINE 3 COLUMN 2 VALUE 'SOLD TO:'.
           02  LINE 3 COLUMN 41 VALUE 'SHIP TO:'.
           02  LINE 8 COLUMN 42 VALUE
             '         ACCOUNT     DATE      NUMBER'.
           02  LINE 10 COLUMN 2 VALUE
             'SLSM  ORDER    BY     SHIP VIA      '.
           02  LINE 10 COLUMN 39 VALUE
             'DATE    TERMS  DISCOUNT       TERMS '.
           02  LINE 13 COLUMN 7 VALUE
             'ITEM             DESCRIPTION      '.
           02  LINE 13 COLUMN 42 VALUE
             'CODE  ORDR  SHPD    PRICE    EXTENSION'.
       01  SCRN-12.
           02  SCRN-CUST.
             03  LINE 9 COLUMN 51 PIC X(6) USING SC-SHIP-CUST.
           02  SCRN-INVC-1.
             03  LINE 9 COLUMN 61 PIC X(9) FROM PR-INVC-DATE.
           02  SCRN-INVC-2.
             03  LINE 9 COLUMN 73 PIC X(6) FROM ST-INVC-NMBR.
       01  SCRN-13.
           02  SCRN-NAME.
             03  LINE 3 COLUMN 11 PIC X(30) FROM PR-NAME.
           02  SCRN-ADDR.
             03  LINE 4 COLUMN 11 PIC X(30) FROM PR-ADDR-2.
             03  LINE 5 COLUMN 11 PIC X(30) FROM PR-ADDR-3.
             03  LINE 6 COLUMN 11 PIC X(30) FROM PR-ADDR-4.
       01  SCRN-14.
           02  SCRN-SHIP-NAME.
             03  LINE 3 COLUMN 50 PIC X(30) USING PR-SHIP-NAME.
           02  SCRN-SHIP-ADDR.
             03  LINE 4 COLUMN 50 PIC X(30) USING PR-SHIP-ADDR-2.
             03  LINE 5 COLUMN 50 PIC X(30) USING PR-SHIP-ADDR-3.
             03  LINE 6 COLUMN 50 PIC X(30) USING PR-SHIP-ADDR-4.
       01  SCRN-15.
           02  SCRN-SLSM.
             03  LINE 11 COLUMN 2 PIC XX USING PR-SLSM.
           02  SCRN-ORDR-NMBR.
             03  LINE 11 COLUMN 7 PIC X(6) TO PR-ORDR-NMBR.
           02  SCRN-BY.
             03  LINE 11 COLUMN 17 PIC XX USING SC-BY.
           02  SCRN-VIA.
             03  LINE 11 COLUMN 21 PIC X(14) TO PR-SHIP-VIA.
      *    02  SCRN-SHIP-DATE.
      *      03  LINE 11 COLUMN 37 PIC X(9) USING SC-DATE.
       01  SCRN-TERMS.
           02  LINE 11 COLUMN 49 PIC X USING SC-TERMS.
           02  LINE 11 COLUMN 52 PIC Z(4).99 FROM ST-CASH-DISC.
           02  LINE 11 COLUMN 63 PIC X(16) FROM PR-TERMS.
       01  SCRN-DISP.
           02  LINE 14 COLUMN 2 PIC X(78) FROM DS-LINE-1.
           02  LINE 15 COLUMN 2 PIC X(78) FROM DS-LINE-2.
           02  LINE 16 COLUMN 2 PIC X(78) FROM DS-LINE-3.
           02  LINE 17 COLUMN 2 PIC X(78) FROM DS-LINE-4.
           02  LINE 18 COLUMN 2 PIC X(78) FROM DS-LINE-5.
           02  LINE 19 COLUMN 2 PIC X(78) FROM DS-LINE-6.
       01  SCRN-17.
           02  SCRN-ITEM.
             03  LINE 21 COLUMN 2 PIC X(15) USING SC-ITEM.
           02  SCRN-DESC.
             03  LINE 21 COLUMN 18 PIC X(24) USING SC-DESC.
           02  SCRN-CODE.
             03  LINE 21 COLUMN 43 PIC X(5) USING SC-CODE AUTO.
           02  SCRN-QTYS.
             03  LINE 21 COLUMN 49 PIC ZZZ- USING SC-ORDR-QTY.
             03  LINE 21 COLUMN 55 PIC ZZZ- USING SC-SHIP-QTY.
           02  SCRN-PRCE.
             03  LINE 21 COLUMN 62 PIC Z(4).ZZ- USING SC-PRICE.
       01  SCRN-17B.
           02  SCRN-ITEM-2.
             03  LINE 22 COLUMN 2 PIC X(15) USING SC-ITEM-2.
           02  SCRN-DESC-2.
             03  LINE 22 COLUMN 18 PIC X(24) USING SC-DESC-2.
           02  SCRN-PRCE-2.
             03  LINE 22 COLUMN 62 PIC Z(4).ZZ- USING SC-PRICE-2.
       01  SCRN-FKEY.
           02  LINE 24 COLUMN 32 VALUE 'ENTER FUNCTION KEY'.
           02  LINE 24 COLUMN 51 PIC X TO SC-FILL AUTO.
       01  SCRN-CNTR.
           02  LINE 23 COLUMN 2 PIC 99 FROM S1.
           02  LINE 23 COLUMN 4 VALUE '/74'.
       01  SCRN-GCL.
           02  LINE 23 COLUMN 18 VALUE
             "CHANGE TO GCL730 AND TO TERMS CODE 'L' Y/N ?".
           02  LINE 23 COLUMN 63 PIC X USING SC-Y-N AUTO.
	01  SCRN-ADDT-ORDR.
		02  LINE 21 COLUMN 20 VALUE
		  'ADDITIONAL ORDERS ON THIS INVOICE: '.
		02  LINE 21 COLUMN 55 PIC X(6) USING SC-ADDT-ORDR.
       01  SPCL-BLNK.
           02  BLNK-2-6.
             03  LINE 2 COLUMN 2 PIC X(78) FROM SC-BLNK-LINE.
             03  LINE 6 COLUMN 1 VALUE '³' FOREGROUND-COLOR 6.
             03  LINE 6 COLUMN 80 VALUE '³' FOREGROUND-COLOR 6.
             03  BLNK-3-6.
               04  LINE 3 COLUMN 2 PIC X(78) FROM SC-BLNK-LINE.
               04  LINE 4 COLUMN 2 PIC X(78) FROM SC-BLNK-LINE.
               04  LINE 5 COLUMN 2 PIC X(78) FROM SC-BLNK-LINE.
               04  LINE 6 COLUMN 2 PIC X(78) FROM SC-BLNK-LINE.
           02  BLNK-21-23.
             03  LINE 21 COLUMN 2 PIC X(78) FROM SC-BLNK-LINE.
             03  BLNK-23.
               04  LINE 22 COLUMN 2 PIC X(78) FROM SC-BLNK-LINE.
               04  LINE 23 COLUMN 2 PIC X(78) FROM SC-BLNK-LINE.
      *******************
       PROCEDURE DIVISION USING LK-DATA.
      *******************
       START-OF-PROCESSING.
           DISPLAY SCRN-BOX.
           DISPLAY SCRN-HEAD.
		MOVE SPACES TO SC-FUNC-LINE.
		MOVE ' F1 = EXIT' TO SC-FUNC-1.
		DISPLAY SCRN-FUNC.
       OPEN-FILES.
		PERFORM OPEN-SY-FILE.
		PERFORM OPEN-AR-FILE.
		PERFORM OPEN-GL-FILE.
		PERFORM OPEN-IP-FILE.
           PERFORM READ-SY-SEQN THRU READ-SY-SEQN-EXIT.
           IF SY-1-RCRD = HIGH-VALUES STOP RUN.
           MOVE SY-MRKT-MKUP TO WK-MRKT-MKUP.
       SET-ACCT-PERD.
           MOVE SY-GL-PERD TO WK-PERD.
		MOVE WK-PERD-DD TO SC-PERD-DD.
           MOVE WK-MMM-TABL (WK-PERD-MM) TO SC-PERD-MMM.
           MOVE WK-PERD-YY TO SC-PERD-YY.
           DISPLAY SCRN-PERD.
       SCRN-PERD-RTN.
           ACCEPT SCRN-PERD.
           ACCEPT SC-FUNC FROM ESCAPE KEY.
           IF SC-FUNC = 00 NEXT SENTENCE
           ELSE IF SC-FUNC = 02 GO TO TRANSFER-PROCESSING
           ELSE GO TO SCRN-PERD-RTN.
           IF SC-Y-N = 'Y' OR 'y' NEXT SENTENCE
           ELSE GO TO SCRN-HEAD-PERD-DISP.
           MOVE SY-GL-NEXT TO WK-PERD.
		MOVE WK-PERD-DD TO SC-PERD-DD.
           MOVE WK-MMM-TABL (WK-PERD-MM) TO SC-PERD-MMM.
           MOVE WK-PERD-YY TO SC-PERD-YY.
		DISPLAY SCRN-PERD.
	SCRN-HEAD-PERD-DISP.
		MOVE ZERO TO SC-HEAD-TOTL.
		DISPLAY SCRN-FUNC-LINE.
	SCRN-INVC-DATE-DISP.
           ACCEPT WK-INVC-YYMMDD FROM DATE.
		IF WK-INVC-YY < 50 MOVE 20 TO WK-INVC-CN
		ELSE MOVE 19 TO WK-INVC-CN.
		MOVE WK-INVC-DD TO SC-DD.
           MOVE WK-MMM-TABL (WK-INVC-MM) TO SC-MMM.
		MOVE WK-INVC-YY TO SC-YY.
           MOVE '-' TO SC-DATE-D1 SC-DATE-D2.
           MOVE SC-DATE TO SV-DATE.
           DISPLAY SCRN-INVC-DATE.
       SCRN-INVC-DATE-ACCP.
           ACCEPT SCRN-INVC-DATE.
           ACCEPT SC-FUNC FROM ESCAPE KEY.
           IF SC-FUNC = 00 NEXT SENTENCE
           ELSE IF SC-FUNC = 02 GO TO TRANSFER-PROCESSING
           ELSE GO TO SCRN-INVC-DATE-ACCP.
           DISPLAY BOX-24.
           MOVE SC-DATE TO LK-SCRN-DATE.
		CALL 'DATE-RTN' USING LK-DATA.
		IF LK-MESS = SPACES
		  MOVE LK-RCRD-DATE TO WK-INVC-DATE
             MOVE SC-DATE TO SV-DATE
		ELSE
		  GO TO SCRN-INVC-DATE-ACCP.
	SET-FRWD-DATE.
		MOVE WK-INVC-DATE TO WK-FRWD-DATE.
		ADD 1 TO WK-FRWD-MM.
		IF WK-FRWD-MM > 12
		  ADD 1 TO WK-FRWD-YY
		  MOVE 01 TO WK-FRWD-MM.
		IF WK-FRWD-YY < 50 MOVE 20 TO WK-FRWD-CN
		ELSE MOVE 19 TO WK-FRWD-CN.
		IF WK-FRWD-MM = 02 AND WK-FRWD-DD > 28
		  MOVE 28 TO WK-FRWD-DD
		ELSE IF (WK-FRWD-MM = 04 OR 06 OR 09 OR 11)
		  AND WK-FRWD-DD > 30
		  MOVE 30 TO WK-FRWD-DD.
		MOVE WK-FRWD-DD TO PR-DISC-DD PR-TERM-S-DD.
		MOVE WK-MMM-TABL (WK-FRWD-MM) TO PR-DISC-MMM
		  PR-TERM-S-MMM.
		MOVE WK-FRWD-YY TO PR-DISC-YY PR-TERM-S-YY.
	SCRN-GOLD-START.
           MOVE SY-GOLD-PRCE TO SC-GOLD-PRCE.
           DISPLAY SCRN-GOLD.
       SCRN-GOLD-RTN.
           ACCEPT SCRN-GOLD.
           ACCEPT SC-FUNC FROM ESCAPE KEY.
           IF SC-FUNC = 00 NEXT SENTENCE
           ELSE IF SC-FUNC = 02 GO TO TRANSFER-PROCESSING
           ELSE GO TO SCRN-GOLD-RTN.
	SCRN-PRTR-RTN.
           DISPLAY SCRN-PRTR.
           ACCEPT SCRN-PRTR.
           ACCEPT SC-FUNC FROM ESCAPE KEY.
           IF SC-FUNC = 00 NEXT SENTENCE
           ELSE IF SC-FUNC = 02 GO TO TRANSFER-PROCESSING
           ELSE GO TO SCRN-PRTR-RTN.
		OPEN OUTPUT PRNT-FILE.
		MOVE 'Y' TO PRNT-OPEN.
		WRITE PRNT-RCRD FROM LK-RGPR AFTER 0.
           MOVE 'QUOTE' TO ST-INVC-NMBR.
      *
       DATA-LOGIC.
           DISPLAY BLNK-3-6.
           DISPLAY SCRN-BLNK.
           DISPLAY BOX-24.
           MOVE SPACES TO WS-SCREEN.
           MOVE 'F1 = EXIT' TO WS-FUNC-1.
           DISPLAY SCRN-FUNC-LINE.
           MOVE SPACES TO ST-LINE-TABLE ST-LINE-TABLE-2.
           MOVE SPACES TO DS-LINES PR-LINES SC-DATA.
           MOVE ZEROS TO ST-TOTLS ST-GL-AR S1 ST-PST PR-PAGE.
           DISPLAY SCRN-MASK.
           DISPLAY SCRN-CUST.
       SCRN-CUST-RTN.
           ACCEPT SCRN-CUST.
           ACCEPT SC-FUNC FROM ESCAPE KEY.
           IF SC-FUNC = 00 NEXT SENTENCE
           ELSE IF SC-FUNC = 02 GO TO TRANSFER-PROCESSING
           ELSE GO TO SCRN-CUST-RTN.
           DISPLAY BOX-24.
           MOVE SPACES TO AR-1-RCRD.
           MOVE SC-SHIP-CUST TO AR-1-CUST.
           MOVE '1' TO AR-1-TYPE.
		PERFORM READ-AR-INDX THRU READ-AR-INDX-EXIT.
		IF AR-1-DATA = SPACES
             MOVE '      CUSTOMER NOT FOUND      ' TO SC-MESS
             DISPLAY SCRN-MESS
             GO TO SCRN-CUST-RTN.
       MOVE-AR-DETAIL.
           MOVE AR-1-NAME TO PR-SHIP-NAME.
           MOVE AR-1-ADDR-2 TO PR-SHIP-ADDR-2.
           MOVE AR-1-ADDR-3 TO PR-SHIP-ADDR-3.
           MOVE AR-1-ADDR-4 TO PR-SHIP-ADDR-4.
           MOVE AR-1-TERMS TO SC-TERMS.
           MOVE AR-1-TERR TO PR-SLSM.
           MOVE AR-1-PST-STAT TO SC-PST-LIC.
           MOVE AR-1-GST-STAT TO SC-GST-STAT.
--->       MOVE AR-1-DISC TO ST-DISC-RATE.
           MOVE SV-DATE TO PR-INVC-DATE.
      *    MOVE 'dd-mmm-yy' TO SC-DATE.
           IF AR-1-GRP = SPACES GO TO DISPLAY-CUST.
           GO TO EDIT-SHIP-CUST.
       DISPLAY-CUST.
           MOVE AR-1-NAME TO PR-NAME.
           MOVE AR-1-ADDR-2 TO PR-ADDR-2.
           MOVE AR-1-ADDR-3 TO PR-ADDR-3.
           MOVE AR-1-ADDR-4 TO PR-ADDR-4.
           DISPLAY SCRN-13.
           DISPLAY BOX-24.
           MOVE SPACES TO WS-FUNC-1.
           MOVE ' F2 = CANCEL' TO WS-FUNC-2.
           DISPLAY SCRN-FUNC-LINE.
           DISPLAY SCRN-14.
           GO TO EDIT-SHIP-NAME.
      *            ACCEPT SCRN-SHIP-CUST.
       EDIT-SHIP-CUST.
           MOVE AR-1-GRP TO SC-BILL-CUST.
           MOVE SPACES TO AR-1-RCRD.
           MOVE SC-BILL-CUST TO AR-1-CUST.
           MOVE '1' TO AR-1-TYPE.
		PERFORM READ-AR-INDX THRU READ-AR-INDX-EXIT.
           IF AR-1-DATA = SPACES
             MOVE '   GROUP ACCOUNT NOT FOUND    ' TO SC-MESS
             DISPLAY SCRN-MESS
             GO TO SCRN-CUST-RTN.
           MOVE AR-1-NAME TO PR-NAME.
           MOVE AR-1-ADDR-2 TO PR-ADDR-2.
           MOVE AR-1-ADDR-3 TO PR-ADDR-3.
           MOVE AR-1-ADDR-4 TO PR-ADDR-4.
		IF SC-TERMS = SPACES
             MOVE AR-1-TERMS TO SC-TERMS.
--->       MOVE AR-1-DISC TO ST-DISC-RATE.
           DISPLAY SCRN-13.
           DISPLAY BOX-24.
           MOVE SPACES TO WS-FUNC-1.
           MOVE ' F2 = CANCEL' TO WS-FUNC-2.
           DISPLAY SCRN-FUNC-LINE.
           DISPLAY SCRN-14.
       EDIT-SHIP-NAME.
           ACCEPT SCRN-SHIP-NAME.
           ACCEPT SC-FUNC FROM ESCAPE KEY.
           IF SC-FUNC = 00 NEXT SENTENCE
           ELSE IF SC-FUNC = 03 GO TO DATA-LOGIC
           ELSE GO TO EDIT-SHIP-NAME.
           IF PR-SHIP-NAME = 'SAME'
             GO TO SCRN-15-START.
       EDIT-SHIP-ADDR-2.
           ACCEPT SCRN-SHIP-ADDR.
           IF SC-FUNC = ZERO NEXT SENTENCE
           ELSE IF SC-FUNC = 03 GO TO DATA-LOGIC
           ELSE GO TO EDIT-SHIP-ADDR-2.
       SCRN-15-START.
           DISPLAY SCRN-CUST.
           DISPLAY SCRN-INVC-1.
           DISPLAY SCRN-15.
       SCRN-15-RTN.
           ACCEPT SCRN-15.
       SCRN-15-FKEY.
           ACCEPT SC-FUNC FROM ESCAPE KEY.
           IF SC-FUNC = 00 NEXT SENTENCE
           ELSE IF SC-FUNC = 03 GO TO DATA-LOGIC
           ELSE GO TO SCRN-15-RTN.
           DISPLAY BOX-24.
           IF PR-SLSM = SPACES
             MOVE '   SALESMAN NUMBER REQUIRED   ' TO SC-MESS
             DISPLAY SCRN-MESS
             ACCEPT SCRN-SLSM
             GO TO SCRN-15-FKEY.
       SCRN-15-BY-EDIT.
           IF SC-BY = 'PH' OR 'ph' MOVE 'PH' TO SC-BY
           ELSE IF SC-BY = 'MO' OR 'mo' MOVE 'MO' TO SC-BY
           ELSE IF SC-BY = 'BO' OR 'bo' MOVE 'BO' TO SC-BY
           ELSE IF SC-BY = 'CO' OR 'co' MOVE 'CO' TO SC-BY
           ELSE IF SC-BY = 'FX' OR 'fx' MOVE 'FX' TO SC-BY
           ELSE IF SC-BY = 'RP' OR 'rp' MOVE 'RP' TO SC-BY
           ELSE
             MOVE 'MUST BE PH, MO, BO, CO, FX, RP' TO SC-MESS
             DISPLAY SCRN-MESS
             ACCEPT SCRN-BY
             GO TO SCRN-15-FKEY.
           DISPLAY SCRN-BY.
      *SCRN-SHIP-DATE-RTN.
      *    MOVE SC-DATE TO LK-SCRN-DATE.
      *    CALL 'DATE-RTN' USING LK-DATA.
      *    IF LK-MESS = SPACES NEXT SENTENCE
      *    ELSE ACCEPT SCRN-SHIP-DATE
      *      GO TO SCRN-15-FKEY.
      *    MOVE '-' TO SC-DATE-D1 SC-DATE-D2.
      *    DISPLAY SCRN-SHIP-DATE
      *    MOVE SC-DATE TO PR-SHIP-DATE.
      *
      ***  INVOICE LINES
      *
       INVC-LINE-RTN.
           MOVE ' F10=TOTAL' TO WS-FUNC-5.
           DISPLAY SCRN-FUNC-LINE.
           DISPLAY BOX-24.
       INVC-LOOP.
           ADD 1 TO S1.
           IF S1 = 74 GO TO INVC-SUB-TOTL-RTN.
           DISPLAY BLNK-21-23.
           DISPLAY BOX-24.
           DISPLAY SCRN-CNTR.
       INVC-LOOP-2.
           IF S1 > S2
             MOVE SPACES TO SC-ITEM SC-DESC SC-CODE SC-CATG SC-DESC-PR
             MOVE ZEROS TO SC-ORDR-QTY SC-SHIP-QTY SC-PRICE SC-EXTN
             MOVE SPACES TO SC-ITEM-2 SC-DESC-2 SC-DESC-PR
             MOVE ZEROS TO SC-PRICE-2 ST-EXTN-2
           ELSE
             MOVE ST-LINE-TAB (S1) TO SC-LINE
             MOVE ST-LINE-TAB-2 (S1) TO SC-LINE-2.
           DISPLAY SCRN-17.
           DISPLAY BOX-24.
       SCRN-ITEM-RTN.
           ACCEPT SCRN-ITEM.
           ACCEPT SC-FUNC FROM ESCAPE KEY.
           IF SC-FUNC = 00 NEXT SENTENCE
           ELSE IF SC-FUNC = 03 GO TO DATA-LOGIC
           ELSE IF SC-FUNC = 08 AND WS-FUNC-3 NOT = SPACES
             GO TO INPT-ERASE-RTN
           ELSE IF SC-FUNC = 09 AND WS-FUNC-4 NOT = SPACES
             GO TO INPT-REVW-RTN
           ELSE IF SC-FUNC = 11 GO TO INVC-SUB-TOTL-RTN
           ELSE GO TO SCRN-ITEM-RTN.
           DISPLAY BOX-24.
       GET-IP-ITEM.
           MOVE SPACES TO IP-RCRD.
           MOVE SC-ITEM TO IP-ITEM.
           IF SC-ITEM = SPACES GO TO SCRN-DESC-RTN.
           PERFORM READ-IP-INDX THRU READ-IP-INDX-EXIT.
           IF IP-DATA = SPACES
             MOVE '     FIRST ITEM NOT FOUND     ' TO SC-MESS
             DISPLAY SCRN-MESS
             GO TO SCRN-ITEM-RTN.
           MOVE IP-DESC TO SC-DESC.
           MOVE IP-SALES-ACCT TO SC-CODE.
           MOVE IP-CATG TO SC-CATG.
       GET-IP-PRICE.
           IF IP-METL-HI = '10K' OR '14K' OR '18K' NEXT SENTENCE
           ELSE GO TO GET-PRICE.
      *            PRICE OF GOLD
           MOVE SC-GOLD-PRCE TO WK-PRCE.
      *            PRICE OF GOLD PER GRAM
           COMPUTE WK-PRCE ROUNDED
             = (WK-PRCE * 32.15076 * .0004182 ) + .400.
      *            COMPUTE PER OF GOLD FOR WEIGHT
           COMPUTE WK-PRCE = WK-PRCE * IP-GOLD-GRMS.
           IF IP-METL-HI = '14K' MULTIPLY 1.40 BY WK-PRCE ROUNDED.
           IF IP-METL-HI = '18K' MULTIPLY 1.80 BY WK-PRCE ROUNDED.
      *            NEW COST OF ITEM
           COMPUTE WK-PRCE ROUNDED = WK-PRCE + IP-MTRL + IP-LABR
            + IP-STER-COST + IP-STNE-COST + IP-STAR-COST + IP-STNE-SET.
      *            NEW SELLING PRICE ROUNDED TO .25
           COMPUTE WK-PRCE ROUNDED =
             WK-PRCE * (1 + (IP-MARK-UP * .01)).
           COMPUTE WK-PRCE ROUNDED =
             WK-PRCE * (1 + (WK-MRKT-MKUP * .01)).
           COMPUTE WK-PRCE ROUNDED =
             WK-PRCE * (1 + (IP-SALES-TAX * .01)).
           IF WK-CENT > 75 ADD 1 TO WK-DLLR
             MOVE 00 TO WK-CENT
           ELSE IF WK-CENT > 50 MOVE 75 TO WK-CENT
           ELSE IF WK-CENT > 25 MOVE 50 TO WK-CENT
           ELSE MOVE 25 TO WK-CENT.
      *    MOVE WK-PRCE TO SC-PRICE.
           DISPLAY SCRN-17.
           GO TO SCRN-DESC-RTN.
       GET-PRICE.
      *    MOVE IP-PRCE TO SC-PRICE.
           MOVE IP-PRCE TO WK-PRCE.
           DISPLAY SCRN-17.
       SCRN-DESC-RTN.
           ACCEPT SCRN-DESC.
           ACCEPT SC-FUNC FROM ESCAPE KEY.
           IF SC-FUNC = 00 NEXT SENTENCE
           ELSE IF SC-FUNC = 03 GO TO DATA-LOGIC
           ELSE IF SC-FUNC = 08 AND WS-FUNC-3 NOT = SPACES
             GO TO INPT-ERASE-RTN
           ELSE IF SC-FUNC = 09 AND WS-FUNC-4 NOT = SPACES
             GO TO INPT-REVW-RTN
           ELSE IF SC-FUNC = 11 GO TO INVC-SUB-TOTL-RTN
           ELSE GO TO SCRN-DESC-RTN.
       SCRN-CODE-RTN.
           ACCEPT SCRN-CODE.
           ACCEPT SC-FUNC FROM ESCAPE KEY.
           IF SC-FUNC = 00 NEXT SENTENCE
           ELSE IF SC-FUNC = 03 GO TO DATA-LOGIC
           ELSE IF SC-FUNC = 08 AND WS-FUNC-3 NOT = SPACES
             GO TO INPT-ERASE-RTN
           ELSE IF SC-FUNC = 09 AND WS-FUNC-4 NOT = SPACES
             GO TO INPT-REVW-RTN
           ELSE IF SC-FUNC = 11 GO TO INVC-SUB-TOTL-RTN
           ELSE GO TO SCRN-CODE-RTN.
           DISPLAY BOX-24.
           IF SC-CODE = SPACES GO TO SCRN-QNTY-RTN.
      *    IF SC-CODE > 300 AND SC-CODE < 330 NEXT SENTENCE
      *    ELSE
      *      MOVE '      ACCOUNT NOT FOUND       ' TO SC-MESS
      *      DISPLAY SCRN-MESS
      *      GO TO SCRN-CODE-RTN.
           MOVE SPACES TO GL-1-RCRD.
           MOVE SC-CODE TO GL-1-ACCT.
           MOVE '1' TO GL-1-TYPE.
           PERFORM READ-GL-INDX THRU READ-GL-INDX-EXIT.
           IF GL-1-DATA = SPACES
             MOVE '      ACCOUNT NOT FOUND       ' TO SC-MESS
             DISPLAY SCRN-MESS
             GO TO SCRN-CODE-RTN.
       SCRN-QNTY-RTN.
           ACCEPT SCRN-QTYS.
           ACCEPT SC-FUNC FROM ESCAPE KEY.
           IF SC-FUNC = 00 NEXT SENTENCE
           ELSE IF SC-FUNC = 03 GO TO DATA-LOGIC
           ELSE IF SC-FUNC = 08 AND WS-FUNC-3 NOT = SPACES
             GO TO INPT-ERASE-RTN
           ELSE IF SC-FUNC = 09 AND WS-FUNC-4 NOT = SPACES
             GO TO INPT-REVW-RTN
           ELSE IF SC-FUNC = 11 GO TO INVC-SUB-TOTL-RTN
           ELSE GO TO SCRN-QNTY-RTN.
           DISPLAY BOX-24.
           IF SC-ITEM = SPACES GO TO SCRN-PRCE-RTN.
           IF SC-CODE = SPACES
             MOVE ' GENERAL LEDGER CODE REQUIRED ' TO SC-MESS
             DISPLAY SCRN-MESS
             GO TO SCRN-CODE-RTN.
           IF SC-SHIP-QTY = ZEROS
             MOVE '      INCORRECT QUANTITY      ' TO SC-MESS
             DISPLAY SCRN-MESS
             GO TO SCRN-QNTY-RTN.
       SCRN-PRCE-DISP.
           IF SC-SHIP-CUST-HI = 'CHM'
             AND (SC-CODE NOT = '30506' AND '314' AND '315'
             AND '30507' AND '30508' AND '329' AND '30408'
--->         AND '30407' AND '328')
             COMPUTE WK-PRCE ROUNDED = WK-PRCE * .95.
           MOVE WK-PRCE TO SC-PRICE.
           DISPLAY SCRN-PRCE.
       SCRN-PRCE-RTN.
           ACCEPT SCRN-PRCE.
           ACCEPT SC-FUNC FROM ESCAPE KEY.
           IF SC-FUNC = 00 NEXT SENTENCE
           ELSE IF SC-FUNC = 03 GO TO DATA-LOGIC
           ELSE IF SC-FUNC = 08 AND WS-FUNC-3 NOT = SPACES
             GO TO INPT-ERASE-RTN
           ELSE IF SC-FUNC = 09 AND WS-FUNC-4 NOT = SPACES
             GO TO INPT-REVW-RTN
           ELSE IF SC-FUNC = 11 GO TO INVC-SUB-TOTL-RTN
           ELSE GO TO SCRN-PRCE-RTN.
       CALC-EXTN.
           COMPUTE ST-EXTN ROUNDED = SC-SHIP-QTY * SC-PRICE.
           IF SC-CODE = '315' OR '30508' OR '329' OR '30408'
             ADD ST-EXTN TO ST-PST.
       ITEM-2-LOOP.
           DISPLAY SCRN-17B.
       SCRN-ITEM-2-RTN.
           ACCEPT SCRN-ITEM-2.
           DISPLAY BOX-24.
           ACCEPT SC-FUNC FROM ESCAPE KEY.
           IF SC-FUNC = 00 NEXT SENTENCE
           ELSE IF SC-FUNC = 03 GO TO DATA-LOGIC
           ELSE IF SC-FUNC = 08 AND WS-FUNC-3 NOT = SPACES
             GO TO INPT-ERASE-RTN
           ELSE IF SC-FUNC = 09 AND WS-FUNC-4 NOT = SPACES
             GO TO INPT-REVW-RTN
           ELSE IF SC-FUNC = 11 GO TO INVC-SUB-TOTL-RTN
           ELSE GO TO SCRN-ITEM-2-RTN.
           IF SC-ITEM-2 = SPACES ADD ST-EXTN TO ST-SUB-TOTL
             PERFORM DISPLAY-LINE-RTN
             GO TO SET-WS-FUNC.
       GET-IP-ITEM-2.
           MOVE SPACES TO IP-KEY.
           MOVE SC-ITEM-2 TO IP-ITEM.
           PERFORM READ-IP-INDX THRU READ-IP-INDX-EXIT.
           IF IP-DATA = SPACES
             MOVE '    SECOND ITEM NOT FOUND     ' TO SC-MESS
             DISPLAY SCRN-MESS
             GO TO SCRN-ITEM-2-RTN.
           MOVE IP-DESC TO SC-DESC-2.
       GET-IP-PRICE-2.
           IF IP-METL-HI = '10K' OR '14K' OR '18K' NEXT SENTENCE
           ELSE GO TO GET-PRICE-2.
      *            PRICE OF GOLD
           MOVE SC-GOLD-PRCE TO WK-PRCE.
      *            PRICE OF GOLD PER GRAM
           COMPUTE WK-PRCE = (WK-PRCE * 32.15076 * .0004182 ) + .400.
      *            COMPUTE PER OF GOLD FOR WEIGHT
           COMPUTE WK-PRCE = WK-PRCE * IP-GOLD-GRMS.
           IF IP-METL-HI = '14K' MULTIPLY 1.40 BY WK-PRCE.
           IF IP-METL-HI = '18K' MULTIPLY 1.80 BY WK-PRCE.
      *            NEW COST OF ITEM
           COMPUTE WK-PRCE ROUNDED = WK-PRCE + IP-MTRL + IP-LABR
            + IP-STER-COST + IP-STNE-COST + IP-STAR-COST + IP-STNE-SET.
      *            NEW SELLING PRICE ROUNDED TO .25
           COMPUTE WK-PRCE ROUNDED = WK-PRCE
            * (1 + (IP-MARK-UP * .01)).
           COMPUTE WK-PRCE ROUNDED = WK-PRCE
            * (1 + (IP-SALES-TAX * .01)).
           IF WK-CENT > 75 ADD 1 TO WK-DLLR
             MOVE 00 TO WK-CENT
           ELSE IF WK-CENT > 50 MOVE 75 TO WK-CENT
           ELSE IF WK-CENT > 25 MOVE 50 TO WK-CENT
           ELSE MOVE 25 TO WK-CENT.
      *    MOVE WK-PRCE TO SC-PRICE-2.
           DISPLAY SCRN-17B.
           GO TO SCRN-DESC-2-RTN.
       GET-PRICE-2.
     *     MOVE IP-PRCE TO SC-PRICE-2.
           MOVE IP-PRCE TO WK-PRCE.
           DISPLAY SCRN-17B.
       SCRN-DESC-2-RTN.
           ACCEPT SCRN-DESC-2.
           ACCEPT SC-FUNC FROM ESCAPE KEY.
           IF SC-FUNC = 00 NEXT SENTENCE
           ELSE IF SC-FUNC = 03 GO TO DATA-LOGIC
           ELSE IF SC-FUNC = 08 AND WS-FUNC-3 NOT = SPACES
             GO TO INPT-ERASE-RTN
           ELSE IF SC-FUNC = 09 AND WS-FUNC-4 NOT = SPACES
             GO TO INPT-REVW-RTN
           ELSE IF SC-FUNC = 11 GO TO INVC-SUB-TOTL-RTN
           ELSE GO TO SCRN-DESC-2-RTN.
       SCRN-PRCE-2-DISP.
           IF SC-SHIP-CUST-HI = 'CHM'
--->         AND (SC-CODE NOT = '30506' AND '314' AND '315'
--->         AND '30507' AND '30508' AND '329' AND '30408'
--->         AND '30407' AND '328')
             COMPUTE WK-PRCE ROUNDED = WK-PRCE * .95.
           MOVE WK-PRCE TO SC-PRICE-2.
           DISPLAY SCRN-PRCE-2.
       SCRN-PRCE-2-RTN.
           ACCEPT SCRN-PRCE-2.
           ACCEPT SC-FUNC FROM ESCAPE KEY.
           IF SC-FUNC = 00 NEXT SENTENCE
           ELSE IF SC-FUNC = 03 GO TO DATA-LOGIC
           ELSE IF SC-FUNC = 08 AND WS-FUNC-3 NOT = SPACES
             GO TO INPT-ERASE-RTN
           ELSE IF SC-FUNC = 09 AND WS-FUNC-4 NOT = SPACES
             GO TO INPT-REVW-RTN
           ELSE IF SC-FUNC = 11 GO TO INVC-SUB-TOTL-RTN
           ELSE GO TO SCRN-PRCE-2-RTN.
       CALC-EXTN-2.
           COMPUTE ST-EXTN-2 ROUNDED =
             (SC-PRICE + SC-PRICE-2) * SC-SHIP-QTY.
           ADD ST-EXTN-2 TO ST-SUB-TOTL.
--->       IF SC-CODE = '315' OR '30408' OR '30508' OR '329'
--->         ADD ST-EXTN TO ST-PST.
           PERFORM DISPLAY-LINE-RTN-2.
       SET-WS-FUNC.
--->       IF SC-BILL-CUST = 'GCL720'
--->         AND (SC-CODE = '30407' OR '30408' OR '30506' OR '30507'
--->         OR '30508' OR '314' OR '315' OR '316')
--->         MOVE 'GCL721' TO SC-BILL-CUST
--->         MOVE 'B' TO SC-TERMS.
           IF S1 > S2 MOVE S1 TO S2.
           IF WS-FUNC-3 = SPACES AND S1 = S2
             MOVE ' F7=ERASE' TO WS-FUNC-3
             DISPLAY SCRN-FUNC-LINE.
           IF WS-FUNC-4 = SPACES
             MOVE ' F8=REVIEW' TO WS-FUNC-4
             DISPLAY SCRN-FUNC-LINE.
           GO TO INVC-LOOP.
      *
       DISPLAY-LINE-RTN.
           MOVE DS-LINE-1 TO DS-LINE-0.
           MOVE DS-LINE-2 TO DS-LINE-1.
           MOVE DS-LINE-3 TO DS-LINE-2.
           MOVE DS-LINE-4 TO DS-LINE-3.
           MOVE DS-LINE-5 TO DS-LINE-4.
           MOVE DS-LINE-6 TO DS-LINE-5.
           MOVE SPACES TO DS-LINE-6.
           MOVE 1 TO ITEM-CTR DS-ITEM-CTR.
           PERFORM MOVE-ITEM THRU MOVE-ITEM-END.
           MOVE DS-ITEM TO SC-ITEM-PR.
           MOVE SC-DESC TO DS-DESC.
           MOVE DS-DESC TO SC-DESC-PR.
           MOVE SC-CODE TO DS-CODE.
           MOVE SC-ORDR-QTY TO DS-ORDR-QTY.
           MOVE SC-SHIP-QTY TO DS-SHIP-QTY.
           MOVE SC-PRICE TO DS-PRICE.
           MOVE ST-EXTN TO DS-EXT SC-EXTN.
           MOVE SPACES TO SC-ITEM-2.
           MOVE ZEROS TO SC-PRICE-2.
           DISPLAY SCRN-DISP
           MOVE SC-LINE TO ST-LINE-TAB (S1).
           MOVE SC-LINE-2 TO ST-LINE-TAB-2 (S1).
      *
       DISPLAY-LINE-RTN-2.
           MOVE DS-LINE-1 TO DS-LINE-0.
           MOVE DS-LINE-2 TO DS-LINE-1.
           MOVE DS-LINE-3 TO DS-LINE-2.
           MOVE DS-LINE-4 TO DS-LINE-3.
           MOVE DS-LINE-5 TO DS-LINE-4.
           MOVE DS-LINE-6 TO DS-LINE-5.
           MOVE SPACES TO DS-LINE-6.
           MOVE 1 TO ITEM-CTR DS-ITEM-CTR.
           PERFORM MOVE-ITEM THRU MOVE-ITEM-END.
           MOVE DS-ITEM TO SC-ITEM-PR.
           MOVE SC-DSC-1A TO DS-DSC-1.
           MOVE SC-ITEM-2A TO DS-DSC-2.
           MOVE SC-ITEM-2B TO DS-DSC-LNTH.
           MOVE 'CM' TO DS-DSC-CM.
           MOVE DS-DESC TO SC-DESC-PR.
           MOVE SC-CODE TO DS-CODE.
           MOVE SC-ORDR-QTY TO DS-ORDR-QTY.
           MOVE SC-SHIP-QTY TO DS-SHIP-QTY.
           COMPUTE WK-TOTL-PRICE ROUNDED = SC-PRICE + SC-PRICE-2.
           MOVE WK-TOTL-PRICE TO DS-PRICE.
           MOVE ST-EXTN-2 TO DS-EXT SC-EXTN.
           DISPLAY SCRN-DISP.
           MOVE SC-LINE TO ST-LINE-TAB (S1).
           MOVE SC-LINE-2 TO ST-LINE-TAB-2 (S1).
      *
       MOVE-ITEM.
           IF ITEM-CTR > 15 OR SC-ITEM-N (ITEM-CTR) = SPACES
             GO TO MOVE-ITEM-END.
           IF SC-ITEM-N (ITEM-CTR) = '/' MOVE 10 TO DS-ITEM-CTR
             GO TO MOVE-ITEM-METL.
           MOVE SC-ITEM-N (ITEM-CTR) TO DS-ITEM-N (DS-ITEM-CTR).
           ADD 1 TO ITEM-CTR.
           ADD 1 TO DS-ITEM-CTR.
           GO TO MOVE-ITEM.
       MOVE-ITEM-METL.
           ADD 1 TO ITEM-CTR.
           IF ITEM-CTR > 15 OR SC-ITEM-N (ITEM-CTR) = SPACES
             GO TO MOVE-ITEM-END.
           IF SC-ITEM-N (ITEM-CTR) = '/' GO TO MOVE-ITEM-BULK.
           MOVE SC-ITEM-N (ITEM-CTR) TO DS-ITEM-N (DS-ITEM-CTR).
           ADD 1 TO DS-ITEM-CTR.
           GO TO MOVE-ITEM-METL.
       MOVE-ITEM-BULK.
           ADD 1 TO ITEM-CTR.
           IF ITEM-CTR > 15 OR SC-ITEM-N (ITEM-CTR) = SPACES
             GO TO MOVE-ITEM-END.
           IF SC-ITEM-N (ITEM-CTR) = 'B' OR 'b'
             MOVE 'BULK' TO DS-DSC-BLK.
           GO TO MOVE-ITEM-BULK.
       MOVE-ITEM-END.
           EXIT.
      *
       INPT-ERASE-RTN.
           SUBTRACT 1 FROM S1.
           SUBTRACT ST-LINE-EXT (S1) FROM ST-SUB-TOTL.
           IF ST-LINE-CODE (S1) = '315' OR '319'
             SUBTRACT ST-LINE-EXT (S1) FROM ST-PST.
           SUBTRACT 1 FROM S1.
           SUBTRACT 1 FROM S2.
           MOVE DS-LINE-5 TO DS-LINE-6.
           MOVE DS-LINE-4 TO DS-LINE-5.
           MOVE DS-LINE-3 TO DS-LINE-4.
           MOVE DS-LINE-2 TO DS-LINE-3.
           MOVE DS-LINE-1 TO DS-LINE-2.
           MOVE DS-LINE-0 TO DS-LINE-1.
           DISPLAY SCRN-DISP.
           MOVE SPACES TO WS-FUNC-3.
           DISPLAY SCRN-FUNC-LINE.
           GO TO INVC-LOOP.
      *
       INPT-REVW-RTN.
           MOVE ZEROS TO ST-SUB-TOTL ST-PST S1.
           DISPLAY BLNK-20-23.
           MOVE SPACES TO DS-LINES.
           DISPLAY SCRN-DISP.
           MOVE SPACES TO SC-FUNC-3 SC-FUNC-4.
           DISPLAY SCRN-FUNC-LINE.
           GO TO INVC-LOOP.
      *
       INVC-SUB-TOTL-RTN.
		MOVE SPACES TO WS-FUNC-5.
		DISPLAY SCRN-FUNC-LINE.
           DISPLAY BLNK-21-23.
           DISPLAY BOX-24.
           MOVE SPACES TO SC-CODE SC-DESC SC-ITEM-PR SC-ITEM.
           MOVE ZEROS TO SC-ORDR-QTY SC-SHIP-QTY SC-PRICE SC-EXTN.
           MOVE '             SUB TOTAL' TO SC-DESC.
           MOVE ST-SUB-TOTL TO ST-EXTN.
           PERFORM DISPLAY-LINE-RTN.
           ADD 1 TO S1.
           DISPLAY SCRN-CNTR.
		MOVE ST-SUB-TOTL TO ST-SALE-AMNT.
--->       IF SC-BILL-CUST = 'GCL720' AND ST-SUB-TOTL POSITIVE
--->         AND ST-SUB-TOTL < 100.00
--->         MOVE 'GCL721' TO SC-BILL-CUST
--->         MOVE 'B' TO SC-TERMS.
--->       IF SC-BILL-CUST = 'GCL720' AND ST-SUB-TOTL = ZERO
--->         MOVE 'GCL721' TO SC-BILL-CUST
--->         MOVE 'B' TO SC-TERMS.
       SCRN-GCL-DISP.
           IF SC-BILL-CUST = 'GCL720' AND ST-SUB-TOTL > 800.00
             NEXT SENTENCE
           ELSE
             GO TO INVC-DISC-LINE.
           MOVE 'N' TO SC-Y-N.
           DISPLAY SCRN-GCL.
       SCRN-GCL-ACCP.
           ACCEPT SCRN-GCL.
           IF SC-Y-N = 'Y' OR 'y'
             MOVE 'L' TO SC-TERMS
             MOVE 'GCL730' TO SC-BILL-CUST.
--->   INVC-DISC-LINE.
           IF SC-BILL-CUST = 'GCL720' OR 'GCL730' NEXT SENTENCE
           ELSE GO TO INVC-SHIP-LINE.
           MOVE SPACES TO SC-DESC SC-ITEM SC-CODE DS-ITEM SC-ITEM-PR.
           MOVE ZEROS TO SC-PRICE.
           MOVE '              DISCOUNT' TO SC-DESC.
           MOVE SY-GL-DISC-A TO SC-CODE.
           IF SC-BILL-CUST = 'GCL720'
             COMPUTE ST-MDSE-DISC ROUNDED = ST-SUB-TOTL * ST-DISC-RATE
             / 100 * -1
           ELSE COMPUTE ST-MDSE-DISC ROUNDED = ST-SUB-TOTL * .03 * -1.
           MOVE ST-MDSE-DISC TO ST-EXTN.
           ADD ST-EXTN TO ST-SUB-TOTL ST-SALE-AMNT.
           PERFORM DISPLAY-LINE-RTN.
           ADD 1 TO S1.
           DISPLAY SCRN-CNTR.
           MOVE SPACES TO SC-CODE SC-DESC SC-ITEM-PR SC-ITEM.
           MOVE ZEROS TO SC-ORDR-QTY SC-SHIP-QTY SC-PRICE SC-EXTN.
           MOVE '    ADJUSTED SUB TOTAL' TO SC-DESC.
           MOVE ST-SUB-TOTL TO ST-EXTN.
           PERFORM DISPLAY-LINE-RTN.
           ADD 1 TO S1.
--->       DISPLAY SCRN-CNTR.
       INVC-SHIP-LINE.
           IF ST-SUB-TOTL < 100.00 MOVE '00' TO PR-SLSM.
           MOVE SPACES TO SC-DESC SC-ITEM SC-CODE DS-ITEM SC-ITEM-PR.
           MOVE ZEROS TO SC-PRICE.
           MOVE '      SHIPPING CHARGES' TO SC-DESC.
           DISPLAY SCRN-17.
       INVC-SHIP-LINE-2.
           ACCEPT SCRN-PRCE.
           ACCEPT SC-FUNC FROM ESCAPE KEY.
           IF SC-FUNC = 03 GO TO DATA-LOGIC
           ELSE IF SC-FUNC = 00 NEXT SENTENCE
           ELSE IF SC-FUNC = 08 GO TO INVC-SHIP-LINE
           ELSE GO TO INVC-SHIP-LINE-2.
           MOVE SY-GL-SHIP TO SC-CODE.
           MOVE SC-PRICE TO ST-EXTN.
           MOVE ZEROS TO SC-PRICE.
           ADD ST-EXTN TO ST-SUB-TOTL.
           PERFORM DISPLAY-LINE-RTN.
           ADD 1 TO S1.
           DISPLAY SCRN-CNTR.
       INVC-GST-LINE.
		MOVE ST-SUB-TOTL TO ST-TAX-BASE.
           MOVE SPACES TO SC-DESC SC-CODE.
           IF SC-GST-STAT = 'HST     '
                  MOVE 'HARMONIZED SALES TAX  ' TO SC-DESC
                  COMPUTE ST-EXTN ROUNDED =
                  ST-TAX-BASE * SY-HST-RATE * .01
                  GO TO GST-SKIP.
           MOVE 'GOODS AND SERVICES TAX' TO SC-DESC.
           IF SC-GST-STAT NOT = SPACES
             MOVE ZEROS TO ST-EXTN
		ELSE COMPUTE ST-EXTN ROUNDED =
		  ST-TAX-BASE * SY-GST-RATE * .01.
       GST-SKIP.
           MOVE SY-GL-GST TO SC-CODE.
           ADD ST-EXTN TO ST-SUB-TOTL.
           PERFORM DISPLAY-LINE-RTN.
           ADD 1 TO S1.
           DISPLAY SCRN-CNTR.
       INVC-PST-LINE.
           DISPLAY BLNK-21-23.
           DISPLAY BOX-24.
           MOVE SPACES TO SC-DESC SC-CODE.
           MOVE '  PROVINCIAL SALES TAX' TO SC-DESC.
           IF SC-GST-STAT = 'HST     '
                  MOVE ZEROS TO ST-EXTN
                  GO TO PST-SKIP.
           IF SC-PST-LIC = SPACES
             COMPUTE ST-EXTN = ST-TAX-BASE * SY-PST-RATE * .01
           ELSE
             COMPUTE ST-EXTN = ST-PST * SY-PST-RATE * .01.
       PST-SKIP.
           MOVE SY-GL-PST TO SC-CODE.
           ADD ST-EXTN TO ST-SUB-TOTL.
           PERFORM DISPLAY-LINE-RTN.
           ADD 1 TO S1.
           DISPLAY SCRN-CNTR.
       INVC-TOTL-LINE.
           DISPLAY BLNK-21-23.
           DISPLAY BOX-24.
           MOVE SPACES TO SC-CODE SC-ITEM SC-DESC DS-ITEM SC-ITEM-PR.
           MOVE '     * * * INVOICE TOTAL' TO SC-DESC.
           MOVE ST-SUB-TOTL TO ST-EXTN.
           PERFORM DISPLAY-LINE-RTN.
           ADD 1 TO S1.
           DISPLAY SCRN-CNTR.
           MOVE SPACES TO WS-FUNC-4.
           DISPLAY SCRN-FUNC-LINE.
       SCRN-SLSM-START.
      *    IF ST-SUB-TOTL < 100.00 MOVE '00' TO PR-SLSM.
           DISPLAY SCRN-SLSM.
       SCRN-SLSM-2-RTN.
           ACCEPT SCRN-SLSM.
           ACCEPT SC-FUNC FROM ESCAPE KEY.
           IF SC-FUNC = ZERO NEXT SENTENCE
           ELSE IF SC-FUNC = 03 GO TO DATA-LOGIC
           ELSE GO TO SCRN-SLSM-2-RTN.
           DISPLAY BOX-24.
           IF PR-SLSM = SPACES
             MOVE '   SALESMAN NUMBER REQUIRED   ' TO SC-MESS
             DISPLAY SCRN-MESS
             GO TO SCRN-SLSM-2-RTN.
       SET-TERMS-1.
		IF ST-SUB-TOTL < .01 MOVE 'B' TO SC-TERMS
		ELSE IF SC-TERMS NOT = SPACES NEXT SENTENCE
           ELSE IF ST-SUB-TOTL < 100.00 MOVE 'B' TO SC-TERMS
           ELSE IF ST-SUB-TOTL < 400.00 MOVE 'D' TO SC-TERMS
           ELSE IF ST-SUB-TOTL < 600.00 MOVE 'E' TO SC-TERMS
           ELSE IF ST-SUB-TOTL < 800.00 MOVE 'F' TO SC-TERMS
           ELSE MOVE 'G' TO SC-TERMS.
		IF (SC-TERMS = 'M' OR 'O') AND SC-BILL-CUST = 'CJG130'
		  AND ST-SUB-TOTL < 1000
		  MOVE 'J' TO SC-TERMS
		ELSE IF SC-TERMS = 'O' AND SC-BILL-CUST = 'CJG130'
		  AND ST-SUB-TOTL < 3000
		  MOVE 'M' TO SC-TERMS.
           IF SC-TERMS = 'A'
		  MOVE ZERO TO ST-CASH-DISC
		  MOVE 'COD' TO PR-TERMS
		ELSE IF SC-TERMS = 'B'
		  MOVE ZERO TO ST-CASH-DISC
             MOVE 'NET 30 DAYS' TO PR-TERMS
           ELSE IF SC-TERMS = 'D'
		  COMPUTE ST-CASH-DISC ROUNDED = ST-SALE-AMNT * .02
             MOVE 'NET 30,60' TO PR-TERMS
           ELSE IF SC-TERMS = 'E'
		  COMPUTE ST-CASH-DISC ROUNDED = ST-SALE-AMNT * .03
             MOVE 'NET 30,60,90' TO PR-TERMS
           ELSE IF SC-TERMS = 'F'
		  COMPUTE ST-CASH-DISC ROUNDED = ST-SALE-AMNT * .04
             MOVE 'NET 30,60,90' TO PR-TERMS
           ELSE IF SC-TERMS = 'G'
		  COMPUTE ST-CASH-DISC ROUNDED = ST-SALE-AMNT * .05
             MOVE 'NET 30,60,90,120' TO PR-TERMS
           ELSE IF SC-TERMS = 'H'
		  COMPUTE ST-CASH-DISC ROUNDED = ST-SALE-AMNT * .06
             MOVE 'NET 60 DAYS ' TO PR-TERMS
           ELSE IF SC-TERMS = 'I'
		  COMPUTE ST-CASH-DISC ROUNDED = ST-SALE-AMNT * .08
             MOVE 'NET 60 DAYS ' TO PR-TERMS
           ELSE IF SC-TERMS = 'J' AND SC-BILL-CUST = 'CJG130'
		  COMPUTE ST-CASH-DISC ROUNDED = ST-SALE-AMNT * .10
             MOVE "    PLAN 'J' " TO PR-TERMS
           ELSE IF SC-TERMS = 'J'
		  COMPUTE ST-CASH-DISC ROUNDED = ST-SALE-AMNT * .10
             MOVE 'NET 60 DAYS ' TO PR-TERMS
--->       ELSE IF SC-TERMS = 'K'
--->         COMPUTE ST-CASH-DISC ROUNDED = ST-SALE-AMNT * .07
--->         MOVE 'G.C.L. PLAN ' TO PR-TERMS
           ELSE IF SC-TERMS = 'L'
             MOVE ZERO TO ST-CASH-DISC
             MOVE 'NET 30,60,90,120' TO PR-TERMS
           ELSE IF SC-TERMS = 'M'
		  COMPUTE ST-CASH-DISC ROUNDED = ST-SALE-AMNT * .09
             MOVE "    PLAN 'M'  " TO PR-TERMS
           ELSE IF SC-TERMS = 'O'
		  COMPUTE ST-CASH-DISC ROUNDED = ST-SALE-AMNT * .08
             MOVE "    PLAN 'O'  " TO PR-TERMS.
		MOVE ST-CASH-DISC TO PR-DISC-AMNT.
           DISPLAY SCRN-TERMS.
       SCRN-TERMS-2-RTN.
           ACCEPT SCRN-TERMS.
           ACCEPT SC-FUNC FROM ESCAPE KEY.
           IF SC-FUNC = ZERO NEXT SENTENCE
           ELSE IF SC-FUNC = 03 GO TO DATA-LOGIC
           ELSE GO TO SCRN-TERMS-2-RTN.
           IF ST-SUB-TOTL < 01 AND SC-TERMS NOT = 'B'
             MOVE '     INVALID TERMS CODE       ' TO SC-MESS
             DISPLAY SCRN-MESS
             GO TO SCRN-TERMS-2-RTN.
           IF SC-TERMS = 'A' OR 'B' OR 'D' OR 'E' OR 'F' OR 'G'
--->         OR 'H' OR 'I' OR 'J' OR 'K' OR 'L'
--->         OR 'M' OR 'O' OR 'R' OR 'S'
             NEXT SENTENCE
           ELSE
             MOVE '     INVALID TERMS CODE       ' TO SC-MESS
             DISPLAY SCRN-MESS
             GO TO SCRN-TERMS-2-RTN.
           IF SC-TERMS = 'A'
		  MOVE ZERO TO ST-CASH-DISC
		  MOVE 'COD' TO PR-TERMS
		ELSE IF SC-TERMS = 'B'
		  MOVE ZERO TO ST-CASH-DISC
             MOVE 'NET 30 DAYS' TO PR-TERMS
           ELSE IF SC-TERMS = 'D'
		  COMPUTE ST-CASH-DISC ROUNDED = ST-SALE-AMNT * .02
             MOVE 'NET 30,60' TO PR-TERMS
           ELSE IF SC-TERMS = 'E'
		  COMPUTE ST-CASH-DISC ROUNDED = ST-SALE-AMNT * .03
             MOVE 'NET 30,60,90' TO PR-TERMS
           ELSE IF SC-TERMS = 'F'
		  COMPUTE ST-CASH-DISC ROUNDED = ST-SALE-AMNT * .04
             MOVE 'NET 30,60,90' TO PR-TERMS
           ELSE IF SC-TERMS = 'G'
		  COMPUTE ST-CASH-DISC ROUNDED = ST-SALE-AMNT * .05
             MOVE 'NET 30,60,90,120' TO PR-TERMS
           ELSE IF SC-TERMS = 'H'
		  COMPUTE ST-CASH-DISC ROUNDED = ST-SALE-AMNT * .06
             MOVE 'NET 60 DAYS ' TO PR-TERMS
           ELSE IF SC-TERMS = 'I'
		  COMPUTE ST-CASH-DISC ROUNDED = ST-SALE-AMNT * .08
             MOVE 'NET 60 DAYS ' TO PR-TERMS
           ELSE IF SC-TERMS = 'J' AND SC-BILL-CUST = 'CJG130'
		  COMPUTE ST-CASH-DISC ROUNDED = ST-SALE-AMNT * .10
             MOVE "    PLAN 'J' " TO PR-TERMS
           ELSE IF SC-TERMS = 'J'
		  COMPUTE ST-CASH-DISC ROUNDED = ST-SALE-AMNT * .10
             MOVE 'NET 60 DAYS ' TO PR-TERMS
--->       ELSE IF SC-TERMS = 'K'
--->         COMPUTE ST-CASH-DISC ROUNDED = ST-SALE-AMNT * .07
--->         MOVE 'G.C.L. PLAN' TO PR-TERMS
           ELSE IF SC-TERMS = 'L'
             MOVE ZERO TO ST-CASH-DISC
             MOVE 'NET 30,60,90,120' TO PR-TERMS
           ELSE IF SC-TERMS = 'M'
		  COMPUTE ST-CASH-DISC ROUNDED = ST-SALE-AMNT * .09
             MOVE "    PLAN 'M'  " TO PR-TERMS
           ELSE IF SC-TERMS = 'O'
             COMPUTE ST-CASH-DISC ROUNDED = ST-SALE-AMNT * .08
             MOVE "    PLAN 'O'  " TO PR-TERMS
           ELSE IF SC-TERMS = 'R'
             MOVE ZERO TO ST-CASH-DISC
             MOVE '5 EQUAL PAYMENTS' TO PR-TERMS
           ELSE IF SC-TERMS = 'S'
             MOVE ZERO TO ST-CASH-DISC
             MOVE '6 EQUAL PAYMENTS' TO PR-TERMS.
           MOVE ST-CASH-DISC TO PR-DISC-AMNT.
           DISPLAY SCRN-TERMS.
      *
      ***  PRINT INVOICE ROUTINE
      *
       SCRN-FKEY-START.
           MOVE ' F3 = PRINT' TO WS-FUNC-3.
           DISPLAY SCRN-FUNC-LINE.
           DISPLAY SCRN-FKEY.
       SCRN-FKEY-RTN.
           ACCEPT SCRN-FKEY.
           ACCEPT SC-FUNC FROM ESCAPE KEY.
           IF SC-FUNC = 04 NEXT SENTENCE
           ELSE IF SC-FUNC = 03 GO TO DATA-LOGIC
           ELSE GO TO SCRN-FKEY-RTN.
       PRNT-INVC-START.
		MOVE 'QUOTE' TO ST-INVC-NMBR.
           DISPLAY SCRN-INVC-2.
           ADD ST-SUB-TOTL TO SC-HEAD-TOTL.
           ADD ST-SUB-TOTL TO ST-GL-AR.
           MOVE ZEROS TO PR-PAGE S2.
           MOVE ST-INVC-NMBR TO PR-INVC-NMBR.
           MOVE 88 TO LINE-CTR.
       MOVE-DETAIL.
           ADD 1 TO S2.
           MOVE ST-LINE-TAB (S2) TO SC-LINE.
           MOVE ST-LINE-TAB-2 (S2) TO SC-LINE-2.
           IF SC-DESC-PR = '             SUB TOTAL'
             GO TO TOTAL-RTN.
           MOVE SPACES TO PR-LINE-8.
           IF SC-BILL-CUST = SPACES MOVE SC-SHIP-CUST TO PR-CUST
           ELSE MOVE SC-BILL-CUST TO PR-CUST.
           MOVE SC-DESC-PR TO PR-DESC.
           MOVE SC-ITEM-PR TO PR-ITEM.
           MOVE SC-ORDR-QTY TO PR-ORDR-QTY.
           MOVE SC-SHIP-QTY TO PR-SHIP-QTY.
           IF SC-ITEM-2 = SPACES MOVE SC-PRICE TO PR-PRICE
           ELSE COMPUTE PR-PRICE ROUNDED = SC-PRICE + SC-PRICE-2.
           MOVE SC-EXTN TO PR-EXT.
           IF LINE-CTR = 88 NEXT SENTENCE
           ELSE IF LINE-CTR NOT < LINE-LMT
           WRITE PRNT-RCRD FROM PR-LINE-A AFTER 2.
           IF LINE-CTR < LINE-LMT
             GO TO PRINT-DETAIL.
       WRITE-HEADINGS.
           IF PR-PAGE = 0 WRITE PRNT-RCRD FROM PR-LINE AFTER 0
           ELSE WRITE PRNT-RCRD FROM PR-LINE AFTER PAGE.
           ADD 1 TO PR-PAGE.
           WRITE PRNT-RCRD FROM PR-LINE-1 AFTER 9.
           WRITE PRNT-RCRD FROM PR-LINE-2 AFTER 1.
           WRITE PRNT-RCRD FROM PR-LINE-3 AFTER 1.
           WRITE PRNT-RCRD FROM PR-LINE-4 AFTER 1.
           WRITE PRNT-RCRD FROM PR-LINE-5 AFTER 1.
           WRITE PRNT-RCRD FROM PR-LINE-6 AFTER 5.
           WRITE PRNT-RCRD FROM BLANKLINE AFTER 2.
           WRITE PRNT-RCRD FROM BLANKLINE AFTER 3.
           MOVE ZEROS TO LINE-CTR.
       PRINT-DETAIL.
           WRITE PRNT-RCRD FROM PR-LINE-8 AFTER 1.
           ADD 1 TO LINE-CTR.
           GO TO MOVE-DETAIL.
       TOTAL-RTN.
--->       IF SC-BILL-CUST = 'GCL720' GO TO GCL-TOTAL-RTN.
           IF PR-PAGE = 0
             PERFORM WRITE-HEADINGS.
           COMPUTE LINE-CTR = 30 - LINE-CTR.
           IF SC-TERMS = 'R'
             COMPUTE PR-TERM-S-AMNT ROUNDED = ST-SUB-TOTL / 5
             WRITE PRNT-RCRD FROM PR-TERM-S AFTER LINE-CTR
           ELSE IF SC-TERMS = 'S'
             COMPUTE PR-TERM-S-AMNT ROUNDED = ST-SUB-TOTL / 6
             WRITE PRNT-RCRD FROM PR-TERM-S AFTER LINE-CTR
           ELSE IF ST-CASH-DISC = ZERO
             WRITE PRNT-RCRD FROM BLANKLINE AFTER LINE-CTR
           ELSE IF SC-BILL-CUST = 'CJG130'
             AND (SC-TERMS = 'M' OR 'O')
             WRITE PRNT-RCRD FROM PR-TERM-CJG AFTER LINE-CTR
           ELSE
             WRITE PRNT-RCRD FROM PR-DISC AFTER LINE-CTR.
       TOTAL-RTN-2.
           MOVE SPACES TO PR-LINE-8.
           MOVE SC-DESC-PR TO PR-DESC.
           MOVE SC-EXTN TO PR-EXT.
           IF SC-DESC-PR =  '     * * * INVOICE TOTAL'
             WRITE PRNT-RCRD FROM PR-LINE-8 AFTER 2
             GO TO PROC-INV-END.
		IF SC-EXTN = ZERO MOVE SPACES TO PR-DESC.
           WRITE PRNT-RCRD FROM PR-LINE-8 AFTER 1.
           ADD 1 TO S2.
           MOVE SPACES TO SC-LINE SC-LINE-2.
           MOVE ST-LINE-TAB (S2) TO SC-LINE.
           MOVE ST-LINE-TAB-2 (S2) TO SC-LINE-2.
           GO TO TOTAL-RTN-2.
--->   GCL-TOTAL-RTN.
           IF PR-PAGE = 0
             PERFORM WRITE-HEADINGS.
           COMPUTE LINE-CTR = 30 - LINE-CTR - 1.
           WRITE PRNT-RCRD FROM BLANKLINE AFTER LINE-CTR LINES.
       GCL-TOTAL-RTN-2.
           MOVE SPACES TO PR-LINE-8.
           MOVE SC-DESC-PR TO PR-DESC.
           MOVE SC-EXTN TO PR-EXT.
           IF SC-DESC-PR =  '     * * * INVOICE TOTAL'
             WRITE PRNT-RCRD FROM PR-LINE-8 AFTER 2
             GO TO PROC-INV-END.
           IF SC-EXTN = ZERO MOVE SPACES TO PR-DESC.
           WRITE PRNT-RCRD FROM PR-LINE-8 AFTER 1.
           ADD 1 TO S2.
           MOVE SPACES TO SC-LINE SC-LINE-2.
           MOVE ST-LINE-TAB (S2) TO SC-LINE.
           MOVE ST-LINE-TAB-2 (S2) TO SC-LINE-2.
           GO TO GCL-TOTAL-RTN-2.
       PROC-INV-END.
           WRITE PRNT-RCRD FROM BLANKLINE AFTER PAGE.
           GO TO DATA-LOGIC.
      *
       TRANSFER-PROCESSING.
		IF PRNT-OPEN = 'Y'
		  CLOSE PRNT-FILE.
           CLOSE SY-FILE.
           CLOSE AR-FILE.
           CLOSE GL-FILE.
           CLOSE IP-FILE.
           EXIT PROGRAM.
           STOP RUN.
      *
      ***	READ/WRITE ROUTINES
      *
       OPEN-SY-FILE.
           MOVE LK-COMP TO WS-SY-COMP.
           OPEN I-O SY-FILE.
           IF LK-IO-STAT = '00' NEXT SENTENCE
		ELSE MOVE 'SYOPEN' TO LK-IO-SRCE
		  CALL 'STAT-RTN' USING LK-DATA
		  GO TO OPEN-SY-FILE.
      *
	READ-SY-SEQN.
		READ SY-FILE NEXT
		  AT END MOVE HIGH-VALUES TO SY-1-RCRD
		  GO TO READ-SY-SEQN-EXIT.
		IF LK-IO-STAT = '00' GO TO READ-SY-SEQN-EXIT.
		MOVE 'SYREAD' TO LK-IO-SRCE.
		CALL 'STAT-RTN' USING LK-DATA.
		GO TO READ-SY-SEQN.
	READ-SY-SEQN-EXIT.
		EXIT.
      *
       OPEN-GL-FILE.
           MOVE LK-COMP TO WS-GL-COMP.
           OPEN I-O GL-FILE.
           IF LK-IO-STAT = '00' NEXT SENTENCE
		ELSE MOVE 'GLOPEN' TO LK-IO-SRCE
		  CALL 'STAT-RTN' USING LK-DATA
		  GO TO OPEN-GL-FILE.
      *
	READ-GL-INDX.
		READ GL-FILE INVALID KEY
		  MOVE SPACES TO GL-1-DATA
		  GO TO READ-GL-INDX-EXIT.
		IF LK-IO-STAT = '00' GO TO READ-GL-INDX-EXIT.
		MOVE 'GLREAD' TO LK-IO-SRCE.
		CALL 'STAT-RTN' USING LK-DATA.
		GO TO READ-GL-INDX.
	READ-GL-INDX-EXIT.
		EXIT.
      *
       OPEN-AR-FILE.
           MOVE LK-COMP TO WS-AR-COMP.
           OPEN I-O AR-FILE.
           IF LK-IO-STAT = '00' NEXT SENTENCE
		ELSE MOVE 'AROPEN' TO LK-IO-SRCE
		  CALL 'STAT-RTN' USING LK-DATA
		  GO TO OPEN-AR-FILE.
      *
	READ-AR-INDX.
		READ AR-FILE INVALID KEY
		  MOVE SPACES TO AR-1-RCRD
		  GO TO READ-AR-INDX-EXIT.
		IF LK-IO-STAT = '00' GO TO READ-AR-INDX-EXIT.
		MOVE 'ARREAD' TO LK-IO-SRCE.
		CALL 'STAT-RTN' USING LK-DATA.
		GO TO READ-AR-INDX.
	READ-AR-INDX-EXIT.
		EXIT.
      *
       OPEN-IP-FILE.
           MOVE LK-COMP TO WS-IP-COMP.
           OPEN I-O IP-FILE.
           IF LK-IO-STAT = '00' NEXT SENTENCE
		ELSE MOVE 'IPOPEN' TO LK-IO-SRCE
		  CALL 'STAT-RTN' USING LK-DATA
		  GO TO OPEN-IP-FILE.
      *
	READ-IP-INDX.
		READ IP-FILE INVALID KEY
		  MOVE SPACES TO IP-RCRD
		  GO TO READ-IP-INDX-EXIT.
		IF LK-IO-STAT = '00' GO TO READ-IP-INDX-EXIT.
		MOVE 'IPREAD' TO LK-IO-SRCE.
		CALL 'STAT-RTN' USING LK-DATA.
		GO TO READ-IP-INDX.
	READ-IP-INDX-EXIT.
		EXIT.
