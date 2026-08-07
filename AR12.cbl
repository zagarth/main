       IDENTIFICATION DIVISION.
       PROGRAM-ID. AR12.
      ***
      ***  INVENTORY PRICING
      ***  CADMAN MANUFACTURING
      ***  23-NOV-95 - ADD LOGIC FOR SY-MRKT-MKUP
      ***  16-FEB-96 - ADD LOGIC FOR IP-COST-ACCT
      ***  26-FEB-96 - ADD LOGIC FOR NEW COST METHOD
      ***  21-AUG-96 - ADD LOGIC FOR IP-ANAL-PAGE IP-ANAL-LINE
      ***  18-OCT-96 - ADD LOGIC FOR BILLS OF MATERIAL
      ***  05-NOV-96 - REMOVE LOGIC FOR PART-CNTL
      ***            - ADD LOGIC FOR IP-SMPL & IP-GRUP
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
           SELECT IP-FILE ASSIGN TO WS-IP-FILE-ID
             ORGANIZATION INDEXED
             ACCESS DYNAMIC
             LOCK MODE IS MANUAL
             RECORD KEY IS IP-KEY
             FILE STATUS IS LK-IO-STAT.
           SELECT IC-FILE ASSIGN TO WS-IC-FILE-ID
             ORGANIZATION INDEXED
             ACCESS DYNAMIC
             LOCK MODE IS MANUAL
             RECORD KEY IS IC-KEY
             FILE STATUS IS LK-IO-STAT.
           SELECT BM-FILE ASSIGN TO WS-BM-FILE-ID
             ORGANIZATION INDEXED
             ACCESS DYNAMIC
             LOCK MODE IS MANUAL
             RECORD KEY IS BM-KEY
             ALTERNATE RECORD KEY IS BM-ALTN-KEY
               = BM-PART BM-ITEM
             FILE STATUS IS LK-IO-STAT.
       DATA DIVISION.
       FILE SECTION.
       COPY SY.
       COPY GL.
       COPY IP.
       COPY IC.
       COPY BM.
      ****************
       WORKING-STORAGE SECTION.
      ****************
       01  WS-SY-FILE-ID.
           02  FILLER              PIC X(2) VALUE 'SY'.
           02  WS-SY-COMP          PIC XX.
           02  FILLER              PIC X(4) VALUE '.DAT'.
       01  WS-GL-FILE-ID.
           02  FILLER              PIC X(2) VALUE 'GL'.
           02  WS-GL-COMP          PIC XX.
           02  FILLER              PIC X(4) VALUE '.DAT'.
       01  WS-IP-FILE-ID.
           02  FILLER              PIC X(2) VALUE 'IP'.
           02  WS-IP-COMP          PIC XX.
           02  FILLER              PIC XXXX VALUE '.DAT'.
       01  WS-IC-FILE-ID.
           02  FILLER              PIC X(2) VALUE 'IC'.
           02  WS-IC-COMP          PIC XX.
           02  FILLER              PIC XXXX VALUE '.DAT'.
       01  WS-BM-FILE-ID.
           02  FILLER              PIC X(2) VALUE 'BM'.
           02  WS-BM-COMP          PIC XX.
           02  FILLER              PIC XXXX VALUE '.DAT'.
      *
       01  SC-RCRD.
           02  SC-ITEM             PIC X(15).
           02  SC-DATA.
             03  SC-DESC.
               04  SC-DELETE       PIC X(6).
               04  FILLER          PIC X(18).
             03  SC-PRCE           PIC 9(4)V99.
             03  SC-COST           PIC 9(4)V99.
             03  SC-MTRL           PIC 999V99.
             03  SC-LABR           PIC 999V99.
             03  SC-METL.
               04  SC-METL-HI      PIC XXX.
               04  FILLER          PIC XX.
             03  SC-GOLD-GRMS      PIC 99V999.
             03  SC-GOLD-COST      PIC 999V99.
             03  SC-STER-GRMS      PIC 99V999.
             03  SC-STER-COST      PIC 999V99.
             03  SC-STNE-COST      PIC 999V99.
             03  SC-STAR-COST      PIC 999V99.
             03  SC-STNE-SET       PIC 999V99.
             03  SC-SALES-ACCT     PIC X(9).
             03  SC-SALES-NEXT     PIC S9(5).
             03  SC-SALES-TABL     PIC S9(5) OCCURS 12.
             03  SC-MARK-UP        PIC 99.
             03  SC-SALES-TAX      PIC 99V99.
             03  SC-INFO-1         PIC X(30).
             03  SC-INFO-2         PIC X(24).
             03  SC-ANAL-PAGE      PIC XXX.
             03  SC-ANAL-LINE      PIC XXX.
             03  SC-CATL-PAGE      PIC XXX.
             03  SC-CATL-LINE      PIC XXX.
             03  SC-CATG           PIC XXX.
             03  SC-GRUP           PIC X.
             03  SC-COST-ACCT      PIC X(9).
             03  SC-SMPL           PIC X.      *> Y/N
             03  FILLER            PIC X(16).
      *
       01  SC-BM-RCRD.
           02  SC-BM-KEY.
             03  SC-BM-ITEM        PIC X(15).
             03  SC-BM-PART        PIC X(15).
           02  SC-BM-DATA.
             03  SC-BM-CLSS        PIC XX.
             03  SC-BM-QNTY        PIC 999V999.
             03  FILLER            PIC X(12).
             03  SC-BM-DESC.
               04  SC-BM-DELETE    PIC X(6).
               04  FILLER          PIC X(18).
             03  SC-BM-METL.
               04  SC-BM-METL-HI   PIC XXX.
               04  FILLER          PIC XX.
             03  SC-BM-COST        PIC 9(4)V99.
       01  ST-BM-TABLE.
           02  ST-BM-TABL OCCURS 49.
             03  ST-BM-ITEM        PIC X(15).
             03  ST-BM-PART        PIC X(15).
             03  ST-BM-CLSS        PIC XX.
             03  ST-BM-QNTY        PIC 999V999.
             03  FILLER            PIC X(12).
             03  ST-BM-DESC        PIC X(24).
             03  ST-BM-METL        PIC X(5).
             03  ST-BM-COST        PIC 9(4)V99.
       01  S1                      PIC S99.
       01  S2                      PIC S99.
       01  DS-LINE-LAST            PIC X(78).
       01  DS-LINE-1               PIC X(78).
       01  DS-LINE-2.
           02  FILLER              PIC X(6).
           02  DS-BM-PART          PIC X(15).
           02  FILLER              PIC X(2).
           02  DS-BM-DESC          PIC X(24).
           02  FILLER              PIC X(2).
           02  DS-BM-METL          PIC X(5).
           02  FILLER              PIC XX.
           02  DS-BM-QNTY          PIC ZZZ.999.
           02  FILLER              PIC XX.
           02  DS-BM-COST          PIC ZZZ.99.
      *
       01  WK-COST                 PIC 999V99.
      *
       01  WK-PRCE                 PIC 9999V99.
       01  WK-PRCE-2 REDEFINES WK-PRCE.
           02  WK-DLLR             PIC 9999.
           02  WK-CENT             PIC 99.
      *
       01  DS-GOLD.
           02  FILLER              PIC X(24) VALUE
             '   STANDARD GOLD PRICE: '.
           02  DS-GOLD-PRCE        PIC 9999.
      *
       01  BM-COST-TABLE.
           02  BM-GOLD-COST        PIC 9(4)V99.
           02  BM-STER-COST        PIC 9(4)V99.
           02  BM-MTRL-COST        PIC 9(4)V99.
           02  BM-STNE-COST        PIC 9(4)V99.
           02  BM-LABR-COST        PIC 9(4)V99.
           02  BM-STNE-SET         PIC 9(4)V99.
           02  BM-STAR-COST        PIC 9(4)V99.
           02  BM-COST             PIC 9(4)V99.
      *
       COPY SC.
       01  SCRN-ITEM.
           02  LINE 7 COLUMN 28 VALUE 'ITEM NUMBER'.
           02  LINE 7 COLUMN 43 PIC X(15) USING SC-ITEM.
       01  SCRN-DTLS.
           02  SCRN-DESC.
             03  LINE 8 COLUMN 28 VALUE 'DESCRIPTION'.
             03  LINE 8 COLUMN 43 PIC X(24) USING SC-DESC.
           02  LINE 11 COLUMN 6 VALUE 'MATERIAL COST'.
           02  LINE 11 COLUMN 23 PIC ZZZ.99 USING SC-MTRL.
           02  LINE 12 COLUMN 6 VALUE 'LABOUR COST'.
           02  LINE 12 COLUMN 23 PIC ZZZ.99 USING SC-LABR.
           02  SCRN-DTL-1.
             03  LINE 13 COLUMN 6 VALUE 'MATERIAL METAL'.
             03  LINE 13 COLUMN 22 PIC X(5) USING SC-METL AUTO.
           02  LINE 14 COLUMN 6 VALUE 'GOLD WEIGHT'.
           02  LINE 14 COLUMN 25 PIC ZZ.999 USING SC-GOLD-GRMS.
           02  LINE 14 COLUMN 32 VALUE 'GRAMS'.
           02  SCRN-DTL-2.
             03  LINE 15 COLUMN 6 VALUE 'GOLD COST'.
             03  LINE 15 COLUMN 23 PIC ZZZ.99 USING SC-GOLD-COST.
           02  LINE 16 COLUMN 6 VALUE 'STERLING WEIGHT'.
           02  LINE 16 COLUMN 25 PIC ZZ.999 USING SC-STER-GRMS.
           02  LINE 16 COLUMN 32 VALUE 'GRAMS'.
           02  SCRN-DTL-3.
             03  LINE 17 COLUMN 6 VALUE 'STERLING COST'.
             03  LINE 17 COLUMN 23 PIC ZZZ.99 USING SC-STER-COST.
           02  LINE 18 COLUMN 6 VALUE 'STONE COST'.
           02  LINE 18 COLUMN 23 PIC ZZZ.99 USING SC-STNE-COST.
           02  LINE 19 COLUMN 6 VALUE 'STAR COST'.
           02  LINE 19 COLUMN 23 PIC ZZZ.99 USING SC-STAR-COST.
           02  LINE 20 COLUMN 6 VALUE 'STONE SET'.
           02  LINE 20 COLUMN 23 PIC ZZZ.99 USING SC-STNE-SET.
           02  SCRN-DTL-4.
             03  LINE 21 COLUMN 6 VALUE 'TOTAL COST'.
             03  LINE 21 COLUMN 21 PIC Z,ZZZ.99 FROM SC-COST.
           02  LINE 11 COLUMN 46 VALUE 'MARK-UP'.
           02  LINE 11 COLUMN 71 PIC ZZ USING SC-MARK-UP AUTO.
           02  SCRN-DTL-5.
             03  LINE 12 COLUMN 46 VALUE 'SELLING PRICE'.
             03  LINE 12 COLUMN 69 PIC Z(4).99 USING SC-PRCE.
           02  LINE 13 COLUMN 46 VALUE 'SALES TAX RATE'.
           02  LINE 13 COLUMN 71 PIC ZZ.99 USING SC-SALES-TAX.
           02  SCRN-DTL-6.
             03  LINE 14 COLUMN 46 VALUE 'SALES ACCOUNT'.
             03  LINE 14 COLUMN 70 PIC X(5) USING SC-SALES-ACCT.
           02  SCRN-DTL-7.
             03  LINE 15 COLUMN 46 VALUE 'COST ACCOUNT'.
             03  LINE 15 COLUMN 70 PIC X(5) USING SC-COST-ACCT.
           02  LINE 16 COLUMN 46 VALUE 'CATALOGUE PAGE\LINE        /'.
           02  LINE 16 COLUMN 70 PIC XXX USING SC-CATL-PAGE AUTO.
           02  LINE 16 COLUMN 74 PIC XXX USING SC-CATL-LINE AUTO.
           02  LINE 17 COLUMN 46 VALUE 'ANALYSIS PAGE/LINE         /'.
           02  LINE 17 COLUMN 70 PIC XXX USING SC-ANAL-PAGE AUTO.
           02  LINE 17 COLUMN 74 PIC XXX USING SC-ANAL-LINE AUTO.
           02  SCRN-CATG.
             03  LINE 18 COLUMN 46 VALUE 'CATEGORY'.
             03  LINE 18 COLUMN 70 PIC XXX USING SC-CATG.
           02  SCRN-GRUP.
             03  LINE 19 COLUMN 46 VALUE 'SALES GROUP'.
             03  LINE 19 COLUMN 70 PIC X USING SC-GRUP.
           02  SCRN-SMPL.
             03  LINE 20 COLUMN 46 VALUE 'SAMPLE Y/N ?'.
             03  LINE 20 COLUMN 70 PIC X USING SC-SMPL.
           02  LINE 21 COLUMN 38 VALUE 'NOTE 1:'.
           02  LINE 21 COLUMN 46 PIC X(30) USING SC-INFO-1.
           02  LINE 22 COLUMN 38 VALUE 'NOTE 2:'.
           02  LINE 22 COLUMN 46 PIC X(24) USING SC-INFO-2.
       01  SELL-MESS.
           02  LINE 23 COLUMN 15 VALUE 'CALCULATED SELLING PRICE IS'.
           02  LINE 23 COLUMN 43 PIC Z,ZZZ.99 FROM WK-PRCE.
           02  LINE 23 COLUMN 53 VALUE 'ACCEPT Y/N ?'.
           02  LINE 23 COLUMN 66 PIC X TO SC-Y-N AUTO.
       01  SCRN-BM-TITL.
           02  LINE 16 COLUMN 33 VALUE 'BILL OF MATERIAL'.
           02  LINE 17 COLUMN 51 VALUE
             'METAL QUANTITY   COST'.
           02  LINE 18 COLUMN 13 VALUE 'PART              DESCRIPTION'.
           02  LINE 18 COLUMN 51 VALUE
             '/TYPE PER UNIT PER UNIT'.
       01  SCRN-BM-DATA.
           02  LINE 19 COLUMN 02 PIC X(78) FROM DS-LINE-1.
           02  LINE 20 COLUMN 02 PIC X(78) FROM DS-LINE-2.
           02  LINE 22 COLUMN 02 PIC X(78) FROM SC-BLNK-LINE.
           02  LINE 22 COLUMN 08 PIC X(15) USING SC-BM-PART.
       01  SCRN-BM-QNTY.
           02  LINE 22 COLUMN 25 PIC X(24) USING SC-BM-DESC.
           02  COLUMN 51 PIC X(5) FROM SC-BM-METL.
           02  COLUMN 58 PIC ZZZ.999 USING SC-BM-QNTY.
       01  SCRN-SUMM.
           02  LINE 13 COLUMN 35 VALUE 'COST SUMMARY'.
           02  LINE 14 COLUMN 43 VALUE "PRICE FILE   BILLS OF MAT'L".
           02  LINE 15 COLUMN 21 VALUE 'GOLD'.
           02  LINE 15 COLUMN 45 PIC ZZZ.99 FROM SC-GOLD-COST.
           02  LINE 15 COLUMN 60 PIC ZZZ.99 FROM BM-GOLD-COST.
           02  LINE 16 COLUMN 21 VALUE 'STERLING'.
           02  LINE 16 COLUMN 45 PIC ZZZ.99 FROM SC-STER-COST.
           02  LINE 16 COLUMN 60 PIC ZZZ.99 FROM BM-STER-COST.
           02  LINE 17 COLUMN 21 VALUE 'MATERIALS'.
           02  LINE 17 COLUMN 45 PIC ZZZ.99 FROM SC-MTRL.
           02  LINE 17 COLUMN 60 PIC ZZZ.99 FROM BM-MTRL-COST.
           02  LINE 18 COLUMN 21 VALUE 'STONES'.
           02  LINE 18 COLUMN 45 PIC ZZZ.99 FROM SC-STNE-COST.
           02  LINE 18 COLUMN 60 PIC ZZZ.99 FROM BM-STNE-COST.
           02  LINE 19 COLUMN 21 VALUE 'LABOUR'.
           02  LINE 19 COLUMN 45 PIC ZZZ.99 FROM SC-LABR.
           02  LINE 19 COLUMN 60 PIC ZZZ.99 FROM BM-LABR-COST.
           02  LINE 20 COLUMN 21 VALUE 'STONE SET'.
           02  LINE 20 COLUMN 45 PIC ZZZ.99 FROM SC-STNE-SET.
           02  LINE 20 COLUMN 60 PIC ZZZ.99 FROM BM-STNE-SET.
           02  LINE 21 COLUMN 21 VALUE 'STAR CROSS'.
           02  LINE 21 COLUMN 45 PIC ZZZ.99 FROM SC-STAR-COST.
           02  LINE 21 COLUMN 60 PIC ZZZ.99 FROM BM-STAR-COST.
           02  LINE 22 COLUMN 21 VALUE '   TOTAL COST'.
           02  LINE 22 COLUMN 44 PIC ZZZZ.99 FROM SC-COST.
           02  LINE 22 COLUMN 59 PIC ZZZZ.99 FROM BM-COST.
       01  SCRN-FKEY.
           02  LINE 24 COLUMN 30 VALUE ' ENTER FUNCTION KEY   '.
           02  COLUMN 50 PIC X TO SC-FILL.
      *******************
       PROCEDURE DIVISION USING LK-DATA.
      *******************
       START-OF-PROCESSING.
           DISPLAY SCRN-BOX.
           DISPLAY SCRN-HEAD.
           MOVE SPACES TO SC-FUNC-LINE.
           MOVE ' F1 = EXIT ' TO SC-FUNC-1.
           DISPLAY SCRN-FUNC.
       OPEN-FILES.
           PERFORM OPEN-SY-FILE.
           PERFORM OPEN-GL-FILE.
           PERFORM OPEN-IP-FILE.
           PERFORM OPEN-IC-FILE.
           PERFORM OPEN-BM-FILE.
           PERFORM READ-SY-SEQN THRU READ-SY-SEQN-EXIT.
           IF SY-1-RCRD = HIGH-VALUES STOP RUN.
           MOVE SPACES TO SC-ITEM.
           MOVE SY-GOLD-PRCE TO DS-GOLD-PRCE.
           MOVE DS-GOLD TO SC-HEAD-NAME.
           DISPLAY SCRN-HEAD-NAME.
      *
       SCRN-ITEM-START.
           DISPLAY SCRN-BLNK.
           MOVE SPACES TO SC-FUNC-LINE.
           MOVE '  F1 = EXIT ' TO SC-FUNC-1.
           DISPLAY SCRN-FUNC.
           DISPLAY SCRN-ITEM.
       SCRN-ITEM-RTN.
           ACCEPT SCRN-ITEM.
           ACCEPT SC-FUNC FROM ESCAPE KEY.
           IF SC-FUNC = 00 NEXT SENTENCE
           ELSE IF SC-FUNC = 02 GO TO TRANSFER-PROCESSING
           ELSE GO TO SCRN-ITEM-RTN.
           DISPLAY BOX-24.
           IF SC-ITEM = SPACES GO TO SCRN-ITEM-RTN.
           MOVE SPACES TO IP-RCRD SC-FUNC-LINE.
           MOVE SC-ITEM TO IP-ITEM.
           MOVE ' F2 = CANCEL' TO SC-FUNC-2.
           DISPLAY SCRN-FUNC.
           PERFORM READ-IP-INDX THRU READ-IP-INDX-EXIT.
           IF IP-DATA = SPACES
             MOVE ZEROS TO IP-DATA
             MOVE SPACES TO IP-DESC IP-METL IP-SALES-ACCT IP-COST-ACCT
               IP-INFO-1 IP-INFO-2 IP-CATL-PAGE IP-CATL-LINE IP-CATG
               IP-ANAL-PAGE IP-ANAL-LINE IP-GRUP IP-SMPL IP-FILLER.
       CHANGE-ACCOUNT.
           MOVE IP-DATA TO SC-DATA.
           DISPLAY SCRN-DTLS.
       SCRN-DTLS-RTN.
           MOVE ' F8 = PAGE' TO SC-FUNC-4.
           DISPLAY SCRN-FUNC.
           ACCEPT SCRN-DTLS.
       SCRN-DTLS-EDIT.
           ACCEPT SC-FUNC FROM ESCAPE KEY.
           IF SC-FUNC = 00 OR 09 NEXT SENTENCE
           ELSE IF SC-FUNC = 03 GO TO SCRN-ITEM-START
           ELSE GO TO SCRN-DTLS-RTN.
           DISPLAY BOX-24.
           IF SC-DELETE = 'DELETE' OR 'delete'
             GO TO DELETE-ITEM-START.
       SCRN-CATG-EDIT.
           IF SC-CATG = SPACES
             MOVE '       INVALID CATEGORY       ' TO SC-MESS
             DISPLAY SCRN-MESS
             ACCEPT SCRN-CATG
             GO TO SCRN-DTLS-EDIT.
       SCRN-GRUP-EDIT.
           IF SC-GRUP NOT NUMERIC OR SC-GRUP = ZERO
             MOVE '     INVALID SALES GROUP' TO SC-MESS
             DISPLAY SCRN-MESS
             ACCEPT SCRN-GRUP
             GO TO SCRN-DTLS-EDIT.
       SCRN-SMPL-RTN.
           IF SC-SMPL = 'y' MOVE 'Y' TO SC-SMPL.
       SCRN-GOLD-START.
           IF SC-GOLD-GRMS = ZERO GO TO SCRN-STER-START.
      *            PRICE OF GOLD
           MOVE SY-GOLD-PRCE TO WK-PRCE.
      *            PRICE OF GOLD PER GRAM
           COMPUTE WK-PRCE ROUNDED
             = (WK-PRCE * 32.15076 * .0004182 ) + .400.
      *            COMPUTE PER OF GOLD FOR WEIGHT
           COMPUTE WK-PRCE = WK-PRCE * SC-GOLD-GRMS.
           IF SC-METL = '14K'
             MULTIPLY 1.4 BY WK-PRCE ROUNDED.
           IF SC-METL = '18K'
             MULTIPLY 1.8 BY WK-PRCE ROUNDED.
           MOVE WK-PRCE TO SC-GOLD-COST.
           DISPLAY SCRN-DTL-2.
       SCRN-STER-START.
           IF SC-STER-GRMS = ZERO GO TO SCRN-TOTL-RTN.
           COMPUTE SC-STER-COST ROUNDED = SC-STER-GRMS * .42.
           DISPLAY SCRN-DTL-3.
       SCRN-TOTL-RTN.
           COMPUTE SC-COST ROUNDED = SC-MTRL + SC-LABR +
            SC-GOLD-COST + SC-STER-COST + SC-STNE-COST +
            SC-STAR-COST + SC-STNE-SET.
           DISPLAY SCRN-DTL-4.
       SCRN-SELL-START.
           IF SC-SMPL = 'Y' GO TO SCRN-ACCT-START.
           MOVE SC-COST TO WK-PRCE.
           COMPUTE WK-PRCE ROUNDED = WK-PRCE
            * (1 + (SC-MARK-UP * .01)).
           COMPUTE WK-PRCE ROUNDED = WK-PRCE
            * (1 + (SY-MRKT-MKUP * .01)).
           COMPUTE WK-PRCE ROUNDED = WK-PRCE
            * (1 + (SC-SALES-TAX * .01)).
           IF WK-CENT > 75 ADD 1 TO WK-DLLR
             MOVE 00 TO WK-CENT
           ELSE IF WK-CENT > 50 MOVE 75 TO WK-CENT
           ELSE IF WK-CENT > 25 MOVE 50 TO WK-CENT
           ELSE MOVE 25 TO WK-CENT.
           IF WK-PRCE = SC-PRCE
             GO TO SCRN-ACCT-START.
           MOVE SPACES TO SC-Y-N.
           DISPLAY BOX-24.
           DISPLAY SELL-MESS.
       SCRN-SELL-RTN.
           ACCEPT SELL-MESS.
           ACCEPT SC-FUNC FROM ESCAPE KEY.
           IF SC-FUNC = 00 NEXT SENTENCE
           ELSE IF SC-FUNC = 03 GO TO SCRN-ITEM-START
           ELSE GO TO SCRN-SELL-RTN.
           DISPLAY BLNK-20-23.
           DISPLAY BOX-24.
           IF SC-Y-N = 'Y' OR 'y'
             NEXT SENTENCE
           ELSE
             GO TO SCRN-ACCT-START.
           MOVE WK-PRCE TO SC-PRCE.
           DISPLAY SCRN-DTL-5.
       SCRN-ACCT-START.
           MOVE SPACES TO GL-1-RCRD.
           MOVE SC-SALES-ACCT TO GL-1-ACCT.
           MOVE '1' TO GL-1-TYPE.
           PERFORM READ-GL-INDX THRU READ-GL-INDX-EXIT.
           IF GL-1-DATA = SPACES
             MOVE '    INVALID SALES ACCOUNT     ' TO SC-MESS
             DISPLAY SCRN-MESS
             ACCEPT SCRN-DTL-6
             DISPLAY BOX-24
             GO TO SCRN-ACCT-START.
       SCRN-COST-START.
           MOVE SPACES TO GL-1-RCRD.
           MOVE SC-COST-ACCT TO GL-1-ACCT.
           MOVE '1' TO GL-1-TYPE.
           PERFORM READ-GL-INDX THRU READ-GL-INDX-EXIT.
           IF GL-1-DATA = SPACES
             MOVE '    INVALID COST ACCOUNT     ' TO SC-MESS
             DISPLAY SCRN-MESS
             ACCEPT SCRN-DTL-7
             DISPLAY BOX-24
             GO TO SCRN-COST-START.
       WRITE-TO-FILE.
           MOVE SPACES TO IP-RCRD.
           MOVE SC-ITEM TO IP-ITEM.
           MOVE SC-DATA TO IP-DATA.
           PERFORM WRITE-IP-RCRD THRU WRITE-IP-EXIT.
           GO TO BM-LOGIC.
      *
       DELETE-ITEM-START.
           MOVE SPACES TO IP-RCRD.
           MOVE SC-ITEM TO IP-ITEM.
           PERFORM DELE-IP-RCRD THRU DELE-IP-EXIT.
       DELETE-BM-RTN.
           MOVE SPACES TO BM-RCRD.
           MOVE SC-ITEM TO BM-ITEM.
           START BM-FILE KEY NOT LESS BM-KEY
             INVALID KEY GO TO GET-NEXT-ITEM.
       DELETE-BM-LOOP.
           PERFORM READ-BM-SEQN THRU READ-BM-SEQN-EXIT.
           IF BM-ITEM = SC-ITEM
             PERFORM DELE-BM-RCRD THRU DELE-BM-EXIT
             GO TO DELETE-BM-LOOP.
           GO TO GET-NEXT-ITEM.
      *
      ***  BILL OF MATERIAL LOGIC
      *
       BM-LOGIC.
           MOVE SPACE TO SC-FUNC-LINE.
           MOVE ' F2 = CANCEL' TO SC-FUNC-2.
           MOVE ' F10 = TOTAL' TO SC-FUNC-6.
           DISPLAY SCRN-FUNC.
           DISPLAY SCRN-BLNK.
           DISPLAY SCRN-ITEM.
           DISPLAY SCRN-DESC.
           DISPLAY SCRN-BM-TITL.
           MOVE SPACES TO DS-LINE-LAST DS-LINE-1 DS-LINE-2.
           MOVE ZERO TO S1 S2.
           MOVE SPACES TO ST-BM-TABLE.
       READ-BM-FILE.
           MOVE SPACES TO BM-RCRD.
           MOVE SC-ITEM TO BM-ITEM.
           START BM-FILE KEY NOT LESS BM-KEY
             INVALID KEY GO TO SCRN-BM-DISP.
       READ-BM-LOOP.
           PERFORM READ-BM-SEQN THRU READ-BM-SEQN-EXIT.
           IF BM-ITEM = SC-ITEM NEXT SENTENCE
           ELSE GO TO SCRN-BM-DISP.
           ADD 1 TO S2.
           MOVE BM-RCRD TO ST-BM-TABL (S2).
           GO TO READ-BM-LOOP.
       SCRN-BM-DISP.
           ADD 1 TO S1.
           IF S1 > S2
             MOVE SPACE TO SC-BM-RCRD
             MOVE SC-ITEM TO SC-BM-ITEM
             MOVE ZEROS TO SC-BM-QNTY
           ELSE MOVE ST-BM-TABL (S1) TO SC-BM-RCRD
             MOVE SPACES TO IC-RCRD
             MOVE SC-BM-PART TO IC-PART
             PERFORM READ-IC-INDX THRU READ-IC-INDX-EXIT
             MOVE IC-CLSS TO SC-BM-CLSS
             MOVE IC-DESC TO SC-BM-DESC
             MOVE IC-METL TO SC-BM-METL.
           DISPLAY SCRN-BM-DATA.
       SCRN-BM-PART-ACCP.
           ACCEPT SCRN-BM-DATA.
       SCRN-BM-PART-EDIT.
           ACCEPT SC-FUNC FROM ESCAPE KEY.
           IF SC-FUNC = ZERO NEXT SENTENCE
           ELSE IF SC-FUNC = 03 GO TO SCRN-ITEM-START
           ELSE IF SC-FUNC = 11 GO TO SCRN-SUMM-STRT
           ELSE IF SC-FUNC = 09 AND SC-FUNC-4 NOT = SPACES
             GO TO SCRN-BM-REVW
           ELSE IF SC-FUNC = 08 AND SC-FUNC-3 NOT = SPACES
             GO TO SCRN-BM-ERASE
           ELSE GO TO SCRN-BM-PART-ACCP.
           DISPLAY BOX-24.
       SCRN-BM-QNTY-DISP.
           MOVE SC-BM-PART TO IC-PART.
           PERFORM READ-IC-INDX THRU READ-IC-INDX-EXIT.
           IF IC-DATA = SPACES
             MOVE '        PART NOT FOUND' TO SC-MESS
             DISPLAY SCRN-MESS
             ACCEPT SCRN-BM-DATA
             GO TO SCRN-BM-PART-EDIT.
           IF SC-BM-DESC = SPACES
             MOVE IC-CLSS TO SC-BM-CLSS
             MOVE IC-DESC TO SC-BM-DESC
             MOVE IC-METL TO SC-BM-METL.
           DISPLAY SCRN-BM-QNTY.
       SCRN-BM-QNTY-ACCP.
           ACCEPT SCRN-BM-QNTY.
           COMPUTE SC-BM-COST ROUNDED = SC-BM-QNTY * IC-COST.
       DISPLAY-RTN.
           MOVE DS-LINE-1 TO DS-LINE-LAST.
           MOVE DS-LINE-2 TO DS-LINE-1.
           MOVE SPACES TO DS-LINE-2.
           MOVE SC-BM-PART TO DS-BM-PART.
           MOVE SC-BM-DESC TO DS-BM-DESC.
           MOVE SC-BM-METL TO DS-BM-METL.
           MOVE SC-BM-QNTY TO DS-BM-QNTY.
           MOVE SC-BM-COST TO DS-BM-COST.
           DISPLAY SCRN-BM-DATA.
           MOVE SC-BM-RCRD TO ST-BM-TABL (S1).
           IF S1 > S2 MOVE S1 TO S2.
           IF SC-FUNC-4 = SPACES
             MOVE ' F8 = REVIEW' TO SC-FUNC-4
             DISPLAY SCRN-FUNC.
           IF S1 = S2 AND SC-FUNC-3 = SPACES
             MOVE ' F7 = ERASE' TO SC-FUNC-3
             DISPLAY SCRN-FUNC.
           GO TO SCRN-BM-DISP.
       SCRN-BM-REVW.
           MOVE SPACES TO SC-FUNC-3.
           DISPLAY SCRN-FUNC.
           MOVE SPACES TO DS-LINE-LAST DS-LINE-1 DS-LINE-2.
           DISPLAY SCRN-BM-DATA.
           MOVE ZERO TO S1.
           GO TO SCRN-BM-DISP.
       SCRN-BM-ERASE.
           MOVE SPACES TO SC-FUNC-3.
           DISPLAY SCRN-FUNC.
           SUBTRACT 1 FROM S1.
           MOVE ZERO TO ST-BM-QNTY (S1).
           MOVE DS-LINE-1 TO DS-LINE-2.
           MOVE DS-LINE-LAST TO DS-LINE-1.
           DISPLAY SCRN-BM-DATA.
           SUBTRACT 1 FROM S1.
           GO TO SCRN-BM-DISP.
       SCRN-SUMM-STRT.
           DISPLAY SCRN-BLNK.
           DISPLAY SCRN-ITEM.
           DISPLAY SCRN-DESC.
           MOVE ZERO TO BM-COST-TABLE.
           MOVE SC-LABR TO BM-LABR-COST.
           MOVE SC-STNE-SET TO BM-STNE-SET.
           MOVE SC-STAR-COST TO BM-STAR-COST.
           MOVE ZERO TO S1.
       SCRN-SUMM-LOOP.
           ADD 1 TO S1.
           IF S1 > S2 GO TO SCRN-SUMM-DISP.
           MOVE ST-BM-TABL (S1) TO SC-BM-RCRD.
           IF SC-BM-DELETE = 'DELETE' OR 'delete'
             NEXT SENTENCE
           ELSE IF SC-BM-METL-HI = '10K' OR '14K' OR '18K'
             ADD SC-BM-COST TO BM-GOLD-COST
           ELSE IF SC-BM-METL = 'STER'
             ADD SC-BM-COST TO BM-STER-COST
           ELSE IF SC-BM-METL = 'STONE'
             ADD SC-BM-COST TO BM-STNE-COST
           ELSE
             ADD SC-BM-COST TO BM-MTRL-COST.
           GO TO SCRN-SUMM-LOOP.
       SCRN-SUMM-DISP.
           ADD BM-GOLD-COST BM-STER-COST BM-MTRL-COST BM-STNE-COST
             BM-LABR-COST BM-STNE-SET BM-STAR-COST
             GIVING BM-COST.
           DISPLAY SCRN-SUMM.
       SCRN-FKEY-DISP.
           MOVE SPACES TO SC-FUNC-LINE.
           MOVE ' F2 = CANCEL' TO SC-FUNC-2.
           MOVE ' F9 = NEXT' TO SC-FUNC-5.
           DISPLAY SCRN-FUNC.
           DISPLAY SCRN-FKEY.
       SCRN-FKEY-ACCP.
           ACCEPT SCRN-FKEY.
           ACCEPT SC-FUNC FROM ESCAPE KEY.
           IF SC-FUNC = 03 GO TO GET-NEXT-ITEM
           ELSE IF SC-FUNC = 10 GO TO WRITE-TO-BM-RCRD
           ELSE GO TO SCRN-FKEY-ACCP.
       WRITE-TO-BM-RCRD.
           MOVE ZERO TO S1.
       WRITE-BM-LOOP.
           ADD 1 TO S1.
           IF S1 > S2 GO TO GET-NEXT-ITEM.
           MOVE ST-BM-TABL (S1) TO SC-BM-RCRD.
           MOVE SC-BM-RCRD TO BM-RCRD.
           IF (SC-BM-DELETE = 'DELETE' OR 'delete')
             OR SC-BM-QNTY = ZERO
             PERFORM DELE-BM-RCRD THRU DELE-BM-EXIT
           ELSE
             PERFORM WRITE-BM-RCRD THRU WRITE-BM-EXIT.
           GO TO WRITE-BM-LOOP.
      *
       GET-NEXT-ITEM.
           PERFORM READ-IP-INDX THRU READ-IP-INDX-EXIT.
           IF IP-DATA = SPACES
             MOVE SPACES TO SC-ITEM
             GO TO SCRN-ITEM-START.
           PERFORM READ-IP-SEQN THRU READ-IP-SEQN-EXIT.
           IF IP-RCRD = HIGH-VALUE MOVE SPACES TO SC-ITEM
           ELSE MOVE IP-ITEM TO SC-ITEM.
           GO TO SCRN-ITEM-START.
      *
       TRANSFER-PROCESSING.
           CLOSE SY-FILE.
           CLOSE GL-FILE.
           CLOSE IP-FILE.
           CLOSE IC-FILE.
           CLOSE BM-FILE.
           EXIT PROGRAM.
           STOP RUN.
      *
      ***  READ/WRITE ROUTINES
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
             MOVE SPACES TO IP-DATA
             GO TO READ-IP-INDX-EXIT.
           IF LK-IO-STAT = '00' GO TO READ-IP-INDX-EXIT.
           MOVE 'IPREAD' TO LK-IO-SRCE.
           CALL 'STAT-RTN' USING LK-DATA.
           GO TO READ-IP-INDX.
       READ-IP-INDX-EXIT.
           EXIT.
      *
       READ-IP-SEQN.
           READ IP-FILE NEXT
             AT END MOVE HIGH-VALUES TO IP-RCRD
             GO TO READ-IP-SEQN-EXIT.
           IF LK-IO-STAT = '00' GO TO READ-IP-SEQN-EXIT.
           MOVE 'IPREAD' TO LK-IO-SRCE.
           CALL 'STAT-RTN' USING LK-DATA.
           GO TO READ-IP-SEQN.
       READ-IP-SEQN-EXIT.
           EXIT.
      *
       READ-IP-LOCK.
           READ IP-FILE LOCK.
           IF LK-IO-STAT = '00' GO TO READ-IP-LOCK-EXIT.
           MOVE 'IPREAD' TO LK-IO-SRCE.
           CALL 'STAT-RTN' USING LK-DATA.
           GO TO READ-IP-LOCK.
       READ-IP-LOCK-EXIT.
           EXIT.
      *
       WRITE-IP-RCRD.
           WRITE IP-RCRD INVALID KEY
             PERFORM READ-IP-LOCK THRU READ-IP-LOCK-EXIT
             MOVE SC-DATA TO IP-DATA
             REWRITE IP-RCRD
             UNLOCK GL-FILE.
           IF LK-IO-STAT = '00' NEXT SENTENCE
           ELSE MOVE 'IPWRIT' TO LK-IO-SRCE
             CALL 'STAT-RTN' USING LK-DATA
             GO TO WRITE-IP-RCRD.
       WRITE-IP-EXIT.
           EXIT.
      *
       DELE-IP-RCRD.
           READ IP-FILE LOCK INVALID KEY GO TO DELE-IP-EXIT.
           IF LK-IO-STAT = '00' NEXT SENTENCE
           ELSE MOVE 'IPREAD' TO LK-IO-SRCE
             CALL 'STAT-RTN' USING LK-DATA
             GO TO DELE-IP-RCRD.
           DELETE IP-FILE RECORD.
           UNLOCK IP-FILE.
       DELE-IP-EXIT.
           EXIT.
      *
       OPEN-IC-FILE.
           MOVE LK-COMP TO WS-IC-COMP.
           OPEN I-O IC-FILE.
           IF LK-IO-STAT = '00' NEXT SENTENCE
           ELSE MOVE 'ICOPEN' TO LK-IO-SRCE
             CALL 'STAT-RTN' USING LK-DATA
             GO TO OPEN-IC-FILE.
      *
       READ-IC-INDX.
           READ IC-FILE INVALID KEY
             MOVE SPACES TO IC-DATA
             GO TO READ-IC-INDX-EXIT.
           IF LK-IO-STAT = '00' GO TO READ-IC-INDX-EXIT.
           MOVE 'ICREAD' TO LK-IO-SRCE.
           CALL 'STAT-RTN' USING LK-DATA.
           GO TO READ-IC-INDX.
       READ-IC-INDX-EXIT.
           EXIT.
           EXIT.
      *
       OPEN-BM-FILE.
           MOVE LK-COMP TO WS-BM-COMP.
           OPEN I-O BM-FILE.
           IF LK-IO-STAT = '00' NEXT SENTENCE
           ELSE MOVE 'BMOPEN' TO LK-IO-SRCE
             CALL 'STAT-RTN' USING LK-DATA
             GO TO OPEN-BM-FILE.
      *
       READ-BM-SEQN.
           READ BM-FILE NEXT
             AT END MOVE HIGH-VALUES TO BM-RCRD
             GO TO READ-BM-SEQN-EXIT.
           IF LK-IO-STAT = '00' GO TO READ-BM-SEQN-EXIT.
           MOVE 'BMREAD' TO LK-IO-SRCE.
           CALL 'STAT-RTN' USING LK-DATA.
           GO TO READ-BM-SEQN.
       READ-BM-SEQN-EXIT.
           EXIT.
      *
       READ-BM-INDX.
           READ BM-FILE INVALID KEY
             MOVE SPACES TO BM-DATA
             GO TO READ-BM-INDX-EXIT.
           IF LK-IO-STAT = '00' GO TO READ-BM-INDX-EXIT.
           MOVE 'BMREAD' TO LK-IO-SRCE.
           CALL 'STAT-RTN' USING LK-DATA.
           GO TO READ-BM-INDX.
       READ-BM-INDX-EXIT.
           EXIT.
      *
       READ-BM-LOCK.
           READ BM-FILE LOCK.
           IF LK-IO-STAT = '00' GO TO READ-BM-LOCK-EXIT.
           MOVE 'BMREAD' TO LK-IO-SRCE.
           CALL 'STAT-RTN' USING LK-DATA.
           GO TO READ-BM-LOCK.
       READ-BM-LOCK-EXIT.
           EXIT.
      *
       WRITE-BM-RCRD.
           WRITE BM-RCRD INVALID KEY
             PERFORM READ-BM-LOCK THRU READ-BM-LOCK-EXIT
             MOVE SC-BM-RCRD TO BM-RCRD
             REWRITE BM-RCRD
             UNLOCK BM-FILE.
           IF LK-IO-STAT = '00' GO TO WRITE-BM-EXIT
           ELSE MOVE 'BMWRIT' TO LK-IO-SRCE
             CALL 'STAT-RTN' USING LK-DATA
             GO TO WRITE-BM-RCRD.
       WRITE-BM-EXIT.
           EXIT.
      *
       DELE-BM-RCRD.
           READ BM-FILE LOCK INVALID KEY GO TO DELE-BM-EXIT.
           IF LK-IO-STAT = '00' NEXT SENTENCE
           ELSE MOVE 'BMREAD' TO LK-IO-SRCE
             CALL 'STAT-RTN' USING LK-DATA
             GO TO DELE-BM-RCRD.
           DELETE BM-FILE RECORD.
           UNLOCK BM-FILE.
       DELE-BM-EXIT.
           EXIT.
      *
